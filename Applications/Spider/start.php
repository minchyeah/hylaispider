<?php

use Spider\Spider;
use Spider\Helper;
use Library\Db;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

$spider = new Spider;
$spider->name = \Config\Spider::$name;
$spider->count = 3;
$spider->seed = \Config\Spider::$scan_urls;
$spider->max = 30;
$spider->logFile = __DIR__ . '/zhonghuasuan_access.log';
$spider->listUrlFilter = \Config\Spider::$list_url_regexes;
$spider->contentUrlFilter = \Config\Spider::$content_url_regexes;
// 设置队列
$spider->setQueue('memory', [
        'host' => \Config\Queue::$address,
        'port' => \Config\Queue::$port
 	]);
$spider->afterDownloadPage = function($spider) {
	if($spider->urlType != 'content'){
        return;
    }
    preg_match('/tid=(\d+)/', $spider->url, $matches);
    if(!isset($matches[1]) || !is_numeric($matches[1])){
        return;
    }
    $db = Db::instance(\Config\Database::$hylai);
    $data = array();
    $data['tid'] = $matches[1];
    $row = $db->select('id,tid')->from('pw_spider')->where('tid', $data['tid'])->row();
    if(isset($row['id']) && $row['id'] > 0){
        return;
    }
    $data['url'] = '"'.$spider->url.'"';
    $data['spide_time'] = time();
    $html = $spider->page;
    $fields = \Config\Spider::$fields;
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
                $values = $spider->get_fields_xpath($html, $conf['selector'], $conf['name']);
            }
            elseif ($conf['selector_type']=='css') 
            {
                $values = $spider->get_fields_css($html, $conf['selector'], $conf['name']);
            }
            elseif ($conf['selector_type']=='regex') 
            {
                $values = $spider->get_fields_regex($html, $conf['selector'], $conf['name']);
            }
        }
        if(isset($conf['filter']) && $conf['filter'] && $values){
                $values = preg_replace($conf['filter'], '', $values);
        }
        $spider->log(print_r($values, true));
        if($conf['name'] == 'post_time'){
            $values = strtotime($values);
        }
        $data[$conf['name']] = '"'.addslashes($values).'"';
    }
    $db->insert('pw_spider')->setCols($data)->query();
};
$spider->start();

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

