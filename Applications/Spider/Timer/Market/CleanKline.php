<?php

namespace Timer\Market;

use Timer\Base;

/**
 * 定时清除K线数据
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class CleanKline extends Base
{
    /**
     * 定时清除K线数据
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $now = time();
        $interval = intval(\Config\Timer::$modules['Market\CleanKline']);
        $periods = [];
        if( ($now % 600) < $interval){
            $periods['1min'] = 300;
        }
        if( ($now % 3000) < $interval){
            $periods['5min'] = 300;
        }
        if( ($now % 9000) < $interval){
            $periods['15min'] = 300;
        }
        if( ($now % 18000) < $interval){
            $periods['30min'] = 300;
        }
        if( ($now % 36000) < $interval){
            $periods['60min'] = 500;
        }
        if( ($now % 144000) < $interval){
            $periods['4hour'] = 500;
        }
        if( ($now % 864000) < $interval){
            $periods['1day'] = 1000;
        }
        if(!empty($periods)){
            foreach ($periods as $period=>$size) {
                $this->call('Business\Market\CleanKline', array('period'=>$period, 'size'=>$size));
            }
        }
        unset($now, $periods);
        $this->unlock();
        $this->wait();
    }
}