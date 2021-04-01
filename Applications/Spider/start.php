<?php

use Spider\Spider;
use Spider\Helper;
use Library\Db;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

$spider = new Spider;
$spider->name = \Config\Spider::$name;
$spider->count = \Config\Spider::$tasknum;
$spider->max = 50000;
$spider->logFile = __DIR__ . '/SpiderWorker_access.log';
$spider->hrefType = \Config\Spider::$href_type;
$spider->listUrlFilter = \Config\Spider::$list_url_regexes;
$spider->listPageFilter = \Config\Spider::$list_page_regexes;
$spider->contentUrlFilter = \Config\Spider::$content_url_regexes;
$spider->contentIdFilter = \Config\Spider::$content_id_regexes;

$spider->start();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

