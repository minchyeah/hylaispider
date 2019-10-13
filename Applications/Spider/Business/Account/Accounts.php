<?php

namespace Business\Account;

use Business\Base;

/**
 * 查询所有账户
 * @author Minch<yeah@minch.me>
 * @since 2019-05-27
 */
class Accounts extends Base
{
    /**
     * 定时任更新所有交易对
     */
    public function run($params)
    {
        try{
            $accounts = $this->huobi->get_account_accounts();
            if(is_array($accounts) && isset($accounts['data']) && !empty($accounts['data'])){
                $rows = $this->db->select('id,symbol,type,state')->from('accounts')->query();
                if(is_array($rows) && !empty($rows)){
                    foreach ($rows as $key=>$row) {
                        unset($rows[$key]);
                        $rows[$row['id']] = $row;
                    }
                    unset($key, $row);
                }
                foreach($accounts['data'] as $account){
                    $data = $account;
                    $data['symbol'] = $account['subtype'];
                    unset($data['subtype']);
                    if(!isset($rows[$account['id']])){
                        $this->db->insert('accounts')->cols($data)->query();
                    }else{
                        unset($data['id']);
                        $data['lasttime'] = time();
                        $this->db->update('accounts')
                                ->setCols($data)
                                ->where('id', $account['id'])->query();
                    }
                    unset($data);
                }
                unset($rows,$row);
            }
            unset($accounts);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function margin()
    {
        try{
            $accounts = $this->huobi->margin_balance();
            if(is_array($accounts) && isset($accounts['data']) && !empty($accounts['data'])){
                $rows = $this->db->select('id,symbol,type,state')->from('accounts')->query();
                if(is_array($rows) && !empty($rows)){
                    foreach ($rows as $key=>$row) {
                        unset($rows[$key]);
                        $rows[$row['id']] = $row;
                    }
                    unset($key, $row);
                }
                foreach($accounts['data'] as $account){
                    $data = $account;
                    $data['symbol'] = $account['subtype'];
                    unset($data['subtype']);
                    if(!isset($rows[$account['id']])){
                        $this->db->insert('accounts')->cols($data)->query();
                    }else{
                        unset($data['id']);
                        $data['lasttime'] = time();
                        $this->db->update('accounts')
                                ->setCols($data)
                                ->where('id', $account['id'])->query();
                    }
                    unset($data);
                }
                unset($rows,$row);
            }
            unset($accounts);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
}