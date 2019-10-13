<?php

namespace Timer\Account;

use Timer\Base;

/**
 * 查询所有交易订单并记录到数据库
 * @author Minch<yeah@minch.me>
 * @since 2019-08-02
 */
class Currency extends Base
{
    private $minid = 0;

    /**
     * 定时获取所有订单
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $interval = 0;
        try{
            $this->initminid();
            $minid = $this->globaldata->account_currencys_minid;
            $rows = $this->db->select('id,currency')
                            ->from('currencys')
                            ->where('id>' . $minid)
                            ->orderBy(array('id ASC'))
                            ->limit(3)->query();
            if(is_array($rows) && !empty($rows)){
                foreach($rows as $row){
                    $this->globaldata->account_currencys_minid = $row['id'];
                    $minid = $this->globaldata->account_currencys_minid;
                    $data = $this->db->select('currency,SUM(`field_amount`-`field_fees`) amount,SUM(`field_cash_amount`) cash, MAX(finished_at) lasttime')
                                ->from('orders')
                                ->where('currency', $row['currency'])
                                ->groupBy(array('currency'))
                                ->row();
                    if(empty($data)){
                        continue;
                    }
                    var_dump($data);
                    $this->save($data);
                    unset($data);
                }
                unset($orders, $row, $data);
            }
        }catch(\Exception $e){
            return false;
        }
        $this->unlock();
        $this->wait($interval);
    }

    private function initminid()
    {
        $minid = $this->globaldata->account_currencys_minid;
        if(!$minid){
            $this->globaldata->account_currencys_minid = $this->minid;
        }
    }

    private function save($data)
    {
        $this->db->update('currencys')
                ->set('amount', rtrim($data['amount'], '0'))
                ->set('usdt', rtrim(bcdiv($data['cash'], $data['amount'], 8), '0'))
                ->set('lasttime', substr($data['lasttime'], 0, -3))
                ->where('currency=\'' . $data['currency'] . '\'')->query();
    }
}
