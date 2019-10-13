<?php

namespace Timer\Market;

use Timer\Base;

/**
 * 定时更新K线数据
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Kline extends Base
{
    /**
     * 定时更新K线数据
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $now = time();
        $interval = intval(\Config\Timer::$modules['Market\Kline']);
        $periods = [];
        if( ($now % 60) < $interval){
            $periods['1min'] = 90;
        }
        if( ($now % 300) < $interval){
            $periods['5min'] = 80;
        }
        if( ($now % 900) < $interval){
            $periods['15min'] = 70;
        }
        if( ($now % 1800) < $interval){
            $periods['30min'] = 60;
        }
        if( ($now % 3600) < $interval){
            $periods['60min'] = 50;
        }
        if( ($now % 14400) < $interval){
            $periods['4hour'] = 40;
        }
        if( ($now % 86400) < $interval){
            $periods['1day'] = 30;
        }
        if(!empty($periods)){
            $rows = $this->db->select('currency')->from('balance')
                        ->where('currency<>', 'usdt')->where('currency<>', 'btt')->column();
            if(is_array($rows) && !empty($rows)){
                foreach ($periods as $period=>$size) {
                    foreach ($rows as $row) {
                        $this->call('Business\Market\Kline', array('symbol'=>$row.'usdt', 'period'=>$period, 'size'=>$size));
                        usleep(50000);
                    }
                }
            }
        }
        $this->unlock();
        $this->wait();
    }
}