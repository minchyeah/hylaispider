<?php

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

\Queue\Queue::server();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')){
    \Workerman\Worker::runAll();
}
