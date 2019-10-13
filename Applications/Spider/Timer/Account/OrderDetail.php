<?php

namespace Timer\Account;

use Timer\Base;

/**
 * 查询所有交易订单并记录到数据库
 * @author Minch<yeah@minch.me>
 * @since 2019-08-02
 */
class OrderDetail extends Base
{
	private $minid = 0;

	/**
	 * 定时获取所有订单
	 */
	public function trigger()
	{
		if (!$this->getlock()) {
			return false;
		}
		$interval = 0;
		try {
			$this->initminid();
			$minid = $this->globaldata->account_orders_detail_minid;
			$orders = $this->db->select('id,oid')->from('orders')->where('id>', $minid)->orderBy(array('id ASC'))->limit(3)->query();
			if (is_array($orders) && !empty($orders)) {
				foreach ($orders as $order) {
					$this->globaldata->account_orders_detail_minid = $order['id'];
					$minid = $this->globaldata->account_orders_detail_minid;
					$data = $this->huobi->get_order($order['oid']);
					if (empty($data)) {
						continue;
					}
					$this->save($order['id'], $data[0]);
					unset($data);
				}
				unset($orders, $order, $data);
			}
		} catch (Exception $e) {
			return false;
		}
		$this->unlock();
		$this->wait($interval);
	}

	private function initminid()
	{
		$minid = $this->globaldata->account_orders_detail_minid;
		if (!$minid) {
			$this->globaldata->account_orders_detail_minid = $this->minid;
		}
	}

	private function save($id, $data)
	{
		$this->db->update('orders')->set('price', $data['price'])->where('id', $id)->where('oid', $data['order-id'])->query();
	}
}
