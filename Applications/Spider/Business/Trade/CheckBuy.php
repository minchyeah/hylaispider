<?php

namespace Business\Trade;

use Business\Base;

/**
 * 定时检查是否可交易
 * @author Minch<yeah@minch.me>
 * @since 2019-08-26
 */
class CheckBuy extends Check
{
    public function run($params)
    {
        $symbol = $params['symbol'];
        try{
            if(!$this->checkbuy($symbol)){
                return false;
            }
            $trade = $this->trade($symbol);
            $price = $trade['price'];
            $order = [];
            $order['symbol'] = $symbol;
            $order['price'] = $price;
            $order['amount'] = 1;
            $order['type'] = 'buy';
            $order['dateline'] =time();
            $this->db->insert('symbol_orders')->cols($order)->query();
            unset($params, $trade, $log, $price, $order);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    private function checkKlineMA($symbol)
    {
        $periods = ['5min', '15min', '30min', '60min', '4hour', '1day'];
        foreach ($periods as $period){
            if(!$this->ltma($symbol, $period)){
                return false;
            }
        }
        return true;
    }

    private function ltma($symbol, $period)
    {
        $row = $this->db->select('open,close,high,low,ma5,ma10,ma30')->from('kline')
                ->where('symbol', $symbol)->where('period', $period)
                ->order('kid DESC')->row();
        if($row['ma10']<$row['ma5']){
            unset($row);
            return false;
        }
        if($row['ma30']<$row['ma10']){
            unset($row);
            return false;
        }
        if($row['ma5']<$row['open']){
            unset($row);
            return false;
        }
        if($row['ma5']<$row['close']){
            unset($row);
            return false;
        }
        if(in_array($period, ['30min', '60min', '4hour', '1day'])){
            $mrow = $this->db->select('MAX(`high`) high,MIN(`low`) low')->from('kline')
                    ->where('symbol', $symbol)->where('period', $period)->row();
            bcscale(8);
            if(bcsub($row['high'], $mrow['low']) > bcmul(bcsub($mrow['high'], $mrow['low']), 0.2)){
                unset($mrow);
                return false;
            }
            unset($mrow);
        }
        unset($row);
        return true;
    }

    private function gtma($symbol, $period)
    {
        $row = $this->db->select('open,close,high,low,ma5,ma10,ma30')->from('kline')
                ->where('symbol', $symbol)->where('period', $period)
                ->order('kid DESC')->row();
        if($row['ma10']>$row['ma5']){
            unset($row);
            return false;
        }
        if($row['ma30']>$row['ma10']){
            unset($row);
            return false;
        }
        if($row['ma5']>$row['open']){
            unset($row);
            return false;
        }
        if($row['ma5']>$row['close']){
            unset($row);
            return false;
        }
        unset($row);
        return true;
    }
}