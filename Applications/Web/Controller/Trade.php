<?php

namespace Web\Controller;

class Trade extends Base
{
	public function check($symbol,$period,$ma)
	{
		$field = 'ma5';
		$sql = "SELECT 
a.`kid` AS kid,
a.`symbol` AS symbol,
a.`period` AS period,
a.`{$field}` AS ma,
a.`type` AS type,
@a.{$field} AS prema,
@a.{$field}:= a.{$field} AS tmp5
FROM (
SELECT kid,symbol,period,type,{$field} FROM kline
WHERE symbol='{$symbol}' AND period='{$period}'
ORDER BY kid ASC
) a,
(SELECT @a.{$field}:=0) s;";

		$rows = $this->db()->query($sql);
		if(is_array($rows)){
			foreach ($rows as $key => $row) {
			}
		}
		if($return){
			return $balance;
		}else{
			echo json_encode($balance);
		}
	}

	private function format($val)
	{
		$tmp = rtrim($val, '0');
		if(substr($tmp, -1) == '.'){
			$tmp .= '00';
		}
		return $tmp;
	}
}