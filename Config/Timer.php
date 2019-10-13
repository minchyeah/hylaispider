<?php

/**
 * 定时器配置
 */
namespace Config;

class Timer
{
    /**
     * 定时任务模块
     * @var array( <br />
     *      业务模块名1=>'时间间隔(秒)',  #从现在开始重复执行业务(无结束时间)，时隔时间为0表示任务不可用<br />
     *      业务模块名2=>array('start'=>'执行时间', 'once'=>'一次性任务'), 指定'执行时间'一次性任务<br />
     *      业务模块名3=>array('start'=>'开始时间', 'interval'=>'时间间隔（秒）'), 从'开始时间'按照'时间间隔'重复执行任务<br />
     *      业务模块名4=>array('end'=>'结束时间', 'interval'=>'时间间隔（秒）'),从现在开始到'结束时间'，按照'时间间隔'重复执行任务 <br />
     *      业务模块名5=>array('start'=>'开始时间', 'end'=>'结束时间' 'interval'=>时间间隔（秒）), 从'开始时间'到'结束时间'周期内按照'时间间隔'重复执行任务<br />
     * ) <br />
     * 
     * @uses array( <br />
     *      业务模块名0=>0, 任务不可用<br />
     *      业务模块名1=>3, 每隔3秒执行一次<br />
     *      业务模块名2=>array('start'=>'2018-10-01 00:00:00', 'once'=>1), 2018-10-01 零点执行一次<br />
     *      业务模块名3=>array('start'=>'2018-07-01', 'interval'=>2), 从2018-07-01起，每隔2秒钟执行一次<br />
     *      业务模块名4=>array('end'=>'2019-01-01', 'interval'=>2), 从现在开始每隔2秒执行一次直到2019-01-01后不再执行<br />
     *      业务模块名5=>array('start'=>'2018-06-01 08:25:05', 'end'=>'2018-08-06 20:50:08', 'interval'=>2), 从2018-06-01 08:25:05到2018-08-06 20:50:08每隔2秒执行一次<br />
     * ) <br />
     */
    public static $modules = array(
            'Account\Balance'=>120, // 获取账号余额
            //'Account\Currency'=>0, // 计算交易币当前价格
            'Account\Orders'=>60, // 查询订单
            //'Account\OrderDetail'=>0, // 查询订单详情
            'Common\Symbols'=>5, // 获取交易对
            //'Common\Currencys'=>0, // 获取所有支持的币种
            'Market\CleanKline'=>10,
            'Market\Kline'=>10, // K线数据
            'Trade\Check'=>15, // 检测交易
            'Trade\CheckMargin'=>30, // 检测交易
        );
    
    /**
     * 进程名
     * @var string
     */
    public static $worker_name = 'TimerWorker';
    
    /**
     * 进程数
     * @var string
     */
    public static $worker_count = 2;
    
    /**
     * 向网关发送数据时使用些签名加密，以便业务端验证来源合法性
     * @var string
     */
    public static $gateway_sign = '333333';
}