<?php

use Beanbun\Beanbun;
use Beanbun\Lib\Helper;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';
$beanbun = new Beanbun;
$beanbun->name = 'ZhongHuaSuan';
$beanbun->count = 3;
$beanbun->seed = 'https://list.zhonghuasuan.com/';
$beanbun->max = 30;
$beanbun->logFile = __DIR__ . '/zhonghuasuan_access.log';
$beanbun->listUrlFilter = [
    '/https:\/\/list.zhonghuasuan.com\/cat-0-0-(\d).html/',
];
$beanbun->contentUrlFilter = [
    '/https:\/\/detail.zhonghuasuan.com\/(\d+).html/'
];
// 设置队列
$beanbun->setQueue('memory', [
        'host' => \Config\Queue::$address,
        'port' => \Config\Queue::$port
 	]);
// $beanbun->afterDownloadPage = function($beanbun) {
// 	//file_put_contents(__DIR__ . '/' . md5($beanbun->url), $beanbun->page);
// };
$beanbun->start();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

