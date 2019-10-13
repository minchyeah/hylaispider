<?php

namespace Business\Account;

use Business\Base;

/**
 * 查询账户余额
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Balance extends Base
{
    /**
     * 定时任更新所有交易对
     */
    public function run($params)
    {
        try{
            $balance = $this->huobi->get_account_balance();
            if(is_array($balance) && isset($balance['list']) && !empty($balance['list'])){
                foreach($balance['list'] as $data){
                    $row = $this->db->select('id,currency')->from('balance')->where('currency', $data['currency'])->row();
                    if(!isset($row['id']) && $data['balance'] == 0){
                        continue;
                    }
                    if(!isset($row['id'])){
                        $this->db->insert('balance')->cols(['currency'=>$data['currency']])->query();
                    }
                    if($data['type'] == 'trade'){
                        $this->db->update('balance')
                                ->setCols(['amount'=>$data['balance'],'lasttime'=>time()])
                                ->where('currency', $data['currency'])->query();
                        $gkey = 'account_balance_'.$data['currency'];
                        $this->globaldata->{$gkey} = $data['balance'];
                    }elseif($data['type'] == 'frozen'){
                        $this->db->update('balance')
                                ->setCols(['frozen'=>$data['balance'],'lasttime'=>time()])
                                ->where('currency', $data['currency'])->query();
                    }
                }
                unset($data);
            }
            unset($balance);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function reprice($params)
    {
        $currency = $params['currency'];
        $balance = $this->db->select('id,currency,reset_order_id')->from('balance')->where('currency', $currency)->row();
        $quote = isset($params['quote']) ? $params['quote'] : 'usdt';
        $symbol = $currency.$quote;

        $this->db->update('balance')
                ->setCols(['usdt_amount'=>0,'usdt_price'=>0])
                ->where('currency', $currency)->query();

        $fields = 'symbol,base_currency,quote_currency,
                SUM(IF(direction=\'buy\',field_amount-field_fees,-field_amount)) amount,
                SUM(IF(direction=\'buy\',field_cash_amount,-field_cash_amount)) cash_amount';
        $row = $this->db->select($fields)->from('orders')
                    ->where('symbol', $symbol)->where('id>', intval($balance['reset_order_id']))
                    ->where('state', 'filled')->where('account_id', '9156386')->row();
        unset($fields,$params,$balance,$quote);
        if(is_array($row) && !empty($row['symbol'])){
            $symbols = $this->db->select('price_precision,amount_precision')->from('symbols')->where('symbol', $symbol)->row();
            $data = ['usdt_amount'=>$row['cash_amount'],'usdt_price'=>bcdiv($row['cash_amount'],$row['amount'],$symbols['price_precision'])];
            $rs = $this->db->update('balance')
                    ->setCols($data)
                    ->where('currency', $currency)->query();
            unset($symbols,$data);
        }
    }
}