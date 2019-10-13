<?php

namespace Timer\Trade;

use Timer\Base;

/**
 * 定时检查是否可交易
 * @author Minch<yeah@minch.me>
 * @since 2019-08-26
 */
class Check extends Base
{
    /**
     * 定时检查是否可交易
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $now = time();
        $cnt = count(\Config\Huobi::$symbols);
        $interval = intval(\Config\Timer::$modules['Trade\Check']);
        $periods = $now % ($cnt*$interval);
        foreach (\Config\Huobi::$symbols as $key => $symbol) {
            if($key == floor($periods/$interval)){
                $this->call('Business\Trade\CheckBuy', array('symbol'=>$symbol));
                break;
            }
        }
        unset($now,$cnt,$interval,$periods,$key,$symbol);
        $this->unlock();
        $this->wait();
    }
}