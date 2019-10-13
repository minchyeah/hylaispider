<?php

namespace Business\Trade;

use Business\Base;

/**
 * 卖出交易
 * @author Minch<yeah@minch.me>
 * @since 2019-09-25
 */
class Sell extends Base
{

    public function run($params)
    {
    }

    protected function saveOrder($order)
    {
        $order['dateline'] =time();
        $this->db->insert('symbol_orders')->cols($order)->query();

    }

    protected function historyTrade($symbol)
    {
        $return = [];
        $trades = $this->huobi->history_trade($symbol, 100);
        $price = $min_price = $max_price = $total_price = $count = 0;
        bcscale(8);
        foreach($trades as $row){
            foreach ($row['data'] as $trade){
                if(0 == $min_price){
                    $min_price = $trade['price'];
                }else{
                    $min_price = min($min_price, $trade['price']);
                }
                if(0 == $max_price){
                    $max_price = $trade['price'];
                }else{
                    $max_price = max($max_price, $trade['price']);
                }
                $total_price = bcadd($total_price, $trade['price']);
                $count += 1;
            }
        }
        unset($trades, $row);
        if($count > 0){
            $price = bcdiv($total_price, $count);
        }
        $return = ['price'=>$price, 'min'=>$min_price, 'max'=>$max_price];
        return $return;
    }
}