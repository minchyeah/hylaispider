<?php

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

$worker = new Queue\Server(\Config\Queue::$address, \Config\Queue::$port);

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')){
    \Workerman\Worker::runAll();
}
