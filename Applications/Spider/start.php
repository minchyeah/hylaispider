<?php

use Beanbun\Beanbun;
use Beanbun\Lib\Helper;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';
sleep(1);
$beanbun = new Beanbun;
$beanbun->name = 'ZhongHuaSuan';
$beanbun->count = 5;
$beanbun->seed = 'https://list.zhonghuasuan.com/';
$beanbun->max = 30;
$beanbun->logFile = __DIR__ . '/qiubai_access.log';
$beanbun->urlFilter = [
    '/https:\/\/list.zhonghuasuan.com\/cat-0-0-(\d).html/'
];
// 设置队列
$beanbun->setQueue('memory', [
    'host' => '127.0.0.1',
     'port' => '2207'
 ]);
$beanbun->afterDownloadPage = function($beanbun) {
    $beanbun->log(print_r($beanbun->page, true));
    $beanbun->log(print_r($beanbun->url, true));
};
$beanbun->start();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

