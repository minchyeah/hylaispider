<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \GatewayWorker\Gateway;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

// gateway 进程，这里使用Text协议，可以用telnet测试
$gateway = new Gateway(Config\Gateway::$protocol.'://'.Config\Gateway::$address.':'.Config\Gateway::$port);
// gateway名称，status方便查看
$gateway->name = Config\Gateway::$worker_name;
// gateway进程数
$gateway->count = Config\Gateway::$worker_count;
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = Config\Gateway::$address;
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = Config\Gateway::$inner_start_port;
// 服务注册地址
$gateway->registerAddress = Config\Gateway::$register_address.':'.Config\Gateway::$register_port;
// 设置路由到指定进程
$gateway->router = function($worker_connections, $client_connection, $cmd, $buffer)
{
    // 默认的随机链接
    $key = array_rand($worker_connections);
    if(3 == $cmd && $buffer){
        try {
            // 按名称整理业务进程
            $keys = array();
            foreach (array_keys($worker_connections) as $ak){
                $t = explode(':', $ak);
                $wk = str_replace(array('Worker','Service'), array('', ''), $t[1]);
                $keys[$wk][] = $ak;
            }
            // 解释请求的业务命名空间
            $msg = json_decode($buffer, true);
            $namespace = substr($msg['class'], 0, strpos($msg['class'], '\\'));
            // 根据命名空间随机选择一个链接
            if(isset($keys[$namespace]) && is_array($keys[$namespace]) && !empty($keys[$namespace])){
                $key = $keys[$namespace][array_rand($keys[$namespace])];
            }
        } catch (Exception $e){}
        unset($keys, $msg, $namespace,$t,$wk);
    }
    return $worker_connections[$key];
};

// 心跳间隔
//$gateway->pingInterval = 10;
// 心跳数据
//$gateway->pingData = '{"type":"ping"}';

/* 
// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection , $http_header)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
        {
            $connection->close();
        }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
}; 
*/

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

