<?php

namespace Business\Market;

use Business\Base;

/**
 * 清除K线数据
 * @author Minch<yeah@minch.me>
 * @since 2019-09-10
 */
class CleanKline extends Base
{
    /**
     * 清除K线数据
     */
    public function run($params)
    {
        $period = $params['period'];
        $size = intval($params['size']);
        $rows = $this->db->select('symbol,period,count(id) cnt')->from('kline')
                        ->where('period', $period)->groupBy(['symbol'])->query();
        if($rows){
            foreach ($rows as $row) {
                if( isset($row['cnt']) && $row['cnt']>0 && $row['cnt']>$size){
                    $this->db->execute('DELETE FROM kline WHERE symbol=:symbol AND period=:period ORDER BY kid ASC LIMIT :size',[
                        'symbol'=>$row['symbol'],
                        'period'=>$period,
                        'size'=>$row['cnt']-$size,
                    ]);
                }
            }
        }
        unset($rows, $row, $params, $symbol, $period, $size);
        return true;
    }
}