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
$beanbun->afterDownloadPage = function($beanbun) {
	if($beanbun->urlType != 'content'){
        return;
    }
    $html = $beanbun->page;
    $fields = \Config\Spider::$fields;
    $data = array();
    foreach ($fields as $conf)
    {
        // 当前field抽取到的内容是否是有多项
        $repeated = isset($conf['repeated']) && $conf['repeated'] ? true : false;
        // 当前field抽取到的内容是否必须有值
        $required = isset($conf['required']) && $conf['required'] ? true : false;

        if (empty($conf['name'])) 
        {
            break;
        }
        $values = NULL;
        // 如果定义抽取规则
        if (!empty($conf['selector'])) 
        {
            // 没有设置抽取规则的类型 或者 设置为 xpath
            if (!isset($conf['selector_type']) || $conf['selector_type']=='xpath') 
            {
                // 如果找不到，返回的是false
                $values = $beanbun->get_fields_xpath($html, $conf['selector'], $conf['name']);
            }
            elseif ($conf['selector_type']=='css') 
            {
                $values = $beanbun->get_fields_css($html, $conf['selector'], $conf['name']);
            }
            elseif ($conf['selector_type']=='regex') 
            {
                $values = $beanbun->get_fields_regex($html, $conf['selector'], $conf['name']);
            }
        }
        $beanbun->log(print_r($values, true));
    }
};
$beanbun->start();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

