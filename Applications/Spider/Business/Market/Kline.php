<?php

namespace Business\Market;

use Business\Base;

/**
 * 定时更新所有支持的币种
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Kline extends Base
{
    public function run($params)
    {
        $symbol = $params['symbol'];
        $period = $params['period'];
        $size = $params['size'];
        try{
            $kline = $this->huobi->kline($symbol, $period, $size);
            $tz = date_default_timezone_get();
            date_default_timezone_set('Asia/Hong_Kong');
            foreach($kline as $key=>&$val){
                if($key == 0 && $period == '1min'){
                    $this->setPrice($symbol, $val);
                }
                if($val['open'] < $val['close']){
                    $val['type'] = 'up';
                }else{
                    $val['type'] = 'down';
                }
                $val['kid'] = $val['id'];
                $val['ktime'] = date('Y-m-d H:i:s', $val['id']);
                $val['symbol'] = $symbol;
                $val['period'] = $period;
                unset($val['id']);
                $row = $this->db->select('id,kid')->from('kline')
                            ->where('kid',$val['kid'])->where('symbol', $symbol)->where('period', $period)->row();
                if($row['id']){
                    $this->db->update('kline')->setCols($val)->where('id',$row['id'])->query();
                }else{
                    $this->db->insert('kline')->cols($val)->query();
                }
            }
            date_default_timezone_set($tz);
            unset($params, $kline, $params, $symbol, $period, $size, $val, $tz, $maxkey, $minkey);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    private function setPrice($symbol, $kline)
    {
        $success = true;
        $gdsymbol = $old_gdsymbol = $this->gdata('symbol_'.$symbol);
        bcscale($gdsymbol['price_precision']);
        $price = bcdiv(bcadd($kline['open'], $kline['close']), 2);
        $gdsymbol['price'] = $price;
        $success = $this->gcas('symbol_'.$symbol, $old_gdsymbol, $gdsymbol);
        $this->db->update('symbols')->setCols(['price'=>$price, 'last_time'=>time()])->where('symbol', $symbol)->query();
        unset($kline, $price, $gdsymbol, $old_gdsymbol);
    }
}