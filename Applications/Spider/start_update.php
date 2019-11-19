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

use Library\Db;

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

// TimerWorker
$worker = new \Workerman\Worker();
$worker->count = 2;
$worker->name = 'UpdateWorker';
$worker->onWorkerStart = function () use ($worker)
{
    // 触发任务
    Workerman\Lib\Timer::add(1, 'update', array($worker), false);
};

function update($worker)
{
    $db = Db::instance(\Config\Database::$default);
    $row = $db->select('*')->from('pw_spider')
            ->where('new_tid>', 0)
            ->where('new_state', 1)
            ->order('id ASC')
            ->limit(1)
            ->row();
    if(!isset($row['id'])){
        Workerman\Lib\Timer::add(2, 'update', array($worker), false);
        return;
    }
    $rs = $db->update('pw_spider')
            ->set('new_state', 2)
            ->where('id', $row['id'])
            ->where('new_tid', $row['new_tid'])
            ->where('new_state', 1)
            ->query();
    if($rs !== 1){
        Workerman\Lib\Timer::add(0.3, 'update', array($worker), false);
        return;
    }
    echo date('Y-m-d H:i:s') . ' Update Worker:' . $worker->id.' 正在编辑 tid: '.$row['new_tid'].' URL: '.$row['url'].PHP_EOL;
    $threadData = [
        'subject' => $row['subject'],
        'lastupdate' => time(),
    ];
    $dbm = Db::instance(\Config\Database::$master);
    $tid = $dbm->update('pw_threads')
                ->setCols($threadData)
                ->where('tid', $row['new_tid'])
                ->query();
    if(!$tid){
        echo date('Y-m-d H:i:s') . ' Update Worker:' . $worker->id . ' 编辑失败 tid: '.$row['new_tid'].PHP_EOL;
        return false;
    }
    echo date('Y-m-d H:i:s') . ' Update Worker:' . $worker->id . ' 编辑成功 tid: '.$row['new_tid'].PHP_EOL;

    $tmsgData = [
        'content' => $row['content']
    ];
    $rs = $dbm->update('pw_tmsgs')
                ->setCols($tmsgData)
                ->where('tid', $row['new_tid'])
                ->query();
    if($rs){
        $post_state = 2;
        $sp_domain = $db->select('svalue')
                    ->from('pw_spider_settings')
                    ->where('skey', 'sp_domain')
                    ->single();
        $warm_worlds = [$sp_domain, $row['author'], '大吉大利'];
        foreach ($warm_worlds as $world) {
            if(strpos($row['content'], $world) OR strpos($row['subject'], $world)){
                $post_state = 88;
                break;
            }
        }
        $pud = $db->update('pw_spider')
                ->set('new_state', 0)
                ->where('id', $row['id'])
                ->where('tid', $row['tid'])
                ->where('new_state', 2)
                ->query();
    }
    Workerman\Lib\Timer::add(0.03, 'update', array($worker), false);
}

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')){
    \Workerman\Worker::runAll();
}
