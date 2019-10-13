<?php

/**
 * Huobi配置
 */
namespace Config;

class Huobi
{
    /**
     * 账号ID
     * @var number
     */
    public static $account_id = '9156386';

    /**
     * 访问密钥
     * @var string
     */
    public static $access_key = 'b7d24f3a-0b8973ca-uymylwhfeg-139dc';

    /**
     * 加密密钥
     * @var string
     */
    public static $secret_key = 'ceec1348-9096f681-6be75c2e-97a62';

    /**
     * 关注的交易对
     */
    public static $symbols = ['btcusdt', 'eosusdt'];

}
