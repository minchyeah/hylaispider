<?php

namespace Timer\Trade;

use Timer\Base;

/**
 * 定时检查杠杆交易
 * @author Minch<yeah@minch.me>
 * @since 2019-09-26
 */
class CheckMargin extends Base
{
    /**
     * 定时检查是否可交易
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $this->call('Business\Trade\CheckMargin', []);
        $this->unlock();
        $this->wait();
    }
}