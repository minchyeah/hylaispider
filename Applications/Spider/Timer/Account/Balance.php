<?php

namespace Timer\Account;

use Timer\Base;

/**
 * 查询账户余额
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Balance extends Base
{
    /**
     * 定时更新账户余额
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        try{
            $this->call('Business\Account\Balance', array());
        }catch(\Exception $e){
            return false;
        }
        $this->unlock();
        $this->wait();
    }
}