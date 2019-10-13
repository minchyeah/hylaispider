<?php

namespace Timer\Common;

use Timer\Base;

/**
 * 定时更新所有支持的币种
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Currencys extends Base
{
    /**
     * 定时更新所有支持的币种
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        $interval = 0;
        try{
            $currencys = $this->huobi->currencys();
            if(is_array($currencys) && !empty($currencys)){
                $this->globaldata->currencys = $currencys;
                $interval = 600;
            }
        }catch(\Exception $e){
            return false;
        }
        $this->unlock();
        $this->wait($interval);
    }
}