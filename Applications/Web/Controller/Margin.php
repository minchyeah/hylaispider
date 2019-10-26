<?php

namespace Web\Controller;

class Margin extends Base
{
    private $symbol = '';
    
	public function index($symbol = '')
	{
	    if('' == $symbol){
	        $this->symbol = 'btcusdt';
	    }
		$this->set('data', json_encode($this->data(true)));
		$this->render('margin.html');
	}

	public function data($return = false)
	{
	    
	    if('' == $this->symbol){
	        $this->symbol = 'btcusdt';
	    }
	    $data = [];
	    $balances = $this->huobi()->margin_balance();
	    if(is_array($balances) && '' == $this->symbol){
	        foreach ($balances as $balance){
	            if($balance['fl-price'] == 0 && $balance['fl-type'] == 'safe'){
	                continue;
	            }
	            $account_id = $balance['id'];
	            $symbol = $balance['symbol'];
	            $fl_type = $balance['fl-type'];
	            $fl_price = $balance['fl-price'];
	            $risk_rate = $balance['risk-rate'];
	            // 借款币种
	            $loan_currency = '';
	            // 借款金额
	            $loan_balance = 0;
	            // 借款利息
	            $loan_interest = 0;
	            foreach ($balance['list'] as $value) {
	                if($value['type'] == 'loan' && $value['balance'] != 0){
	                    $loan_currency = $value['currency'];
	                    $loan_balance = abs($value['balance']);
	                    continue;
	                }
	                if($value['type'] == 'interest' && $value['balance'] != 0){
	                    $loan_interest = abs($value['balance']);
	                    continue;
	                }
	            }
	            $balance['currency'] = $loan_currency;
	            $balance['loan_balance'] = $loan_balance;
	            $balance['interest'] = $loan_interest;
	            $data[] = $balance;
	        }
	    }
		if($return){
		    return $data;
		}else{
		    echo json_encode($data);
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