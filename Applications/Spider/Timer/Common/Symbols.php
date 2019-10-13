<?php

namespace Timer\Common;

use Timer\Base;
use \think\facade\Db;

/**
 * 定时任更新所有交易对
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Symbols extends Base
{
    /**
     * 定时任更新所有交易对
     */
    public function trigger()
    {
        if(!$this->getlock()){
            return false;
        }
        try{
            $symbols = $this->huobi->symbols();
            if(is_array($symbols) && !empty($symbols)){
                foreach($symbols as $key=>$val){
                    $cols['symbol'] = $val['symbol'];
                    $cols['base_currency'] = $val['base-currency'];
                    $cols['quote_currency'] = $val['quote-currency'];
                    $cols['price_precision'] = $val['price-precision'];
                    $cols['amount_precision'] = $val['amount-precision'];
                    $cols['symbol_partition'] = $val['symbol-partition'];
                    $cols['state'] = $val['state'];
                    $cols['value_precision'] = $val['value-precision'];
                    $cols['min_order_amt'] = $val['min-order-amt'];
                    $cols['max_order_amt'] = $val['max-order-amt'];
                    $cols['min_order_value'] = $val['min-order-value'];
                    $row = $this->db->select('id,symbol,tradeable')->from('symbols')->where('symbol', $cols['symbol'])->row();
                    if(!isset($row['id'])){
                        $this->db->insert('symbols')->cols($cols)->query();
                    }else{
                        $this->db->update('symbols')->setCols($cols)->where('id', $row['id'])->where('symbol', $cols['symbol'])->query();
                    }
                    if(isset($row['tradeable']) && $row['tradeable'] == 1){
                        $this->globaldata->{'symbol_'.$cols['symbol']} = $cols;
                    }
                }
                unset($symbols, $key, $val, $cols);
                $this->unlock();
                return true;
            }
        }catch(\Exception $e){
        }
        $this->unlock();
        $this->wait();
    }
}