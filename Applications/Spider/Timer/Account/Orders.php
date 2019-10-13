<?php

namespace Timer\Account;

use Timer\Base;

/**
 * 查询所有交易订单并记录到数据库
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Orders extends Base
{
    private $start = '2019-07-01';
    private $end = '2019-07-02';
    private $start_key = '';
    private $end_key = '';

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
            $now = time();
            $symbols = $this->db->select('symbol')->from('symbols')->where('tradeable', 1)->column();
            $cnt = count($symbols);
            $interval = intval(\Config\Timer::$modules['Account\Orders']);
            $periods = $now % ($cnt*$interval);
            foreach ($symbols as $key => $symbol) {
                if($key == floor($periods/$interval)){
                    $this->start_key = 'account_orders_start_date_'.$symbol;
                    $this->end_key = 'account_orders_end_date_'.$symbol;
                    $this->checkdate($symbol);
                    $this->call('Business\Account\Orders', 
                        [
                            'symbol'=>$symbol,
                            'start'=>$this->globaldata->{$this->start_key},
                            'end'=>$this->globaldata->{$this->end_key}
                        ]);
                    break;
                }
            }
        }catch(\Exception $e){
        }
        $this->unlock();
        $this->wait($interval);
    }

    private function checkdate($symbol)
    {
        $start = $this->globaldata->{$this->start_key};
        $end = $this->globaldata->{$this->end_key};
        if(!$start && !$end){
            $data = $this->db->select('MAX(created_at) created_at,MAX(finished_at) finished_at,MAX(canceled_at) canceled_at')->from('orders')->where('symbol', $symbol)->row();
            if(is_array($data) && !empty($data) && intval($data['created_at']) > 0){
                $lasttime = substr(max($data), 0, -3);
                $this->globaldata->{$this->start_key} = date("Y-m-d", $lasttime);
                $this->globaldata->{$this->end_key} = date("Y-m-d", strtotime('+1 day', $lasttime));
            }else{
                $this->globaldata->{$this->start_key} = $this->start;
                $this->globaldata->{$this->end_key} = $this->end;
            }
        }else{
            if(strtotime($start) > time() || strtotime($end) > time()){
                $this->globaldata->{$this->start_key} = date("Y-m-d");
                $this->globaldata->{$this->end_key} = date("Y-m-d", strtotime('+1 day'));
            }else{
                $this->globaldata->{$this->start_key} = date("Y-m-d", strtotime('+1 day', strtotime($end)));
                $this->globaldata->{$this->end_key} = date("Y-m-d", strtotime('+2 day', strtotime($end)));
            }
        }
    }
}