<?php

namespace Business\Trade;

use Business\Base;

/**
 * 定时检查杠杆交易
 * @author Minch<yeah@minch.me>
 * @since 2019-09-26
 */
class CheckMargin extends Base
{

    public function run($params)
    {
        $balances = $this->huobi->margin_balance();
        if(!is_array($balances) || empty($balances)){
            return;
        }
        foreach ($balances as $balance) {
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
            switch ($fl_type) {
                case 'buy':
                    $this->buy($symbol, $price);
                    break;
                case 'sell':
                    $this->sell($symbol, $price);
                    break;
                default:
                    break;
            }
        }
    }

    protected function buy()
    {

    }

    protected function sell()
    {

    }
}