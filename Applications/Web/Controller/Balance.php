<?php

namespace Web\Controller;

class Balance extends Base
{
	public function detail($currency)
	{
		echo 'balance detail ' . $currency;
	}

	public function index()
	{
		$balance = $this->data(true);
		$this->set('data', json_encode($balance));
		$this->render('balance/index.html');
	}

	public function data($return = false)
	{
		$balance = $this->db->select('currency, amount, frozen, usdt_amount, usdt_price, lasttime')
					->from('balance')->where('currency<>', 'usdt')->where('currency<>', 'btt')->query();
		if(is_array($balance)){
			foreach ($balance as $key => &$value) {
				$value['amount'] = $this->format($value['amount']);
				$value['unfrozen'] = $this->format(bcsub($value['amount'], $value['frozen'], 8));
				$value['usdt_amount'] = $this->format($value['usdt_amount']);
				$value['usdt_price'] = $this->format($value['usdt_price']);
				$symbol = $value['currency'].'usdt';
				$value['cash_price'] = $this->format($this->gdata('symbol_'.$symbol)['price']);
				$value['cash_amount'] = $this->format(bcmul($value['amount'], $value['cash_price'],8));
				$value['gainloss'] = $this->format(bcsub($value['cash_amount'], $value['usdt_amount'],8));
				$value['gainloss_rate'] = $value['usdt_amount']==0 ? '0.00%' : bcmul(bcsub(1, bcdiv($value['cash_price'], $value['usdt_price'],6), 6), 100,2).'%';
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