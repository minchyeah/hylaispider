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
$worker->count = 3;
$worker->name = 'PosterWorker';
$worker->onWorkerStart = function () use ($worker)
{
    // 触发任务
    Workerman\Lib\Timer::add(1, 'post', array($worker), false);
};

function post($worker)
{
    $db = Db::instance(\Config\Database::$default);
    $row = $db->select('*')->from('pw_spider')
            ->where('new_tid', 0)
            ->where('state', 0)
            ->order('id ASC')
            ->limit(1)
            ->row();
    if(!isset($row['id'])){
        Workerman\Lib\Timer::add(2, 'post', array($worker), false);
        return;
    }
    $rs = $db->update('pw_spider')
            ->set('state', 1)
            ->where('id', $row['id'])
            ->where('new_tid', 0)
            ->where('state', 0)
            ->query();
    if($rs !== 1){
        Workerman\Lib\Timer::add(0.3, 'post', array($worker), false);
        return;
    }
    $post_author = $db->select('id,author_id,author,sp_author,state')
                    ->from('pw_spider_authors')
                    ->where('sp_author', $row['author'])
                    ->row();
    if(!isset($post_author['id'])){
        Workerman\Lib\Timer::add(0.3, 'post', array($worker), false);

        $pud = $db->update('pw_spider')
                ->set('state', 99)
                ->where('id', $row['id'])
                ->where('tid', $row['tid'])
                ->where('state', 1)
                ->query();
        return;
    }
    echo date('Y-m-d H:i:s') . ' Poster Worker:' . $worker->id.' 正在发布： '.$row['url'].PHP_EOL;
    $threadData = [
        'fid' => 8,
        'icon' => 0,
        'titlefont' => '',
        'author' => $post_author['author'],
        'authorid' => $post_author['author_id'],
        'subject' => $row['subject'],
        'toolinfo' => '',
        'toolfield' => '',
        'ifcheck' => 1,
        'type' => 0,
        'postdate' => time(),
        'lastpost' => time(),
        'lastposter' => $post_author['author'],
        'hits' => 0,
        'replies' => 0,
        'favors' => 0,
        'modelid' => 0,
        'shares' => 0,
        'topped' => 0,
        'topreplays' => 0,
        'locked' => 0,
        'digest' => 0,
        'special' => 0,
        'state' => 0,
        'ifupload' => 0,
        'ifmail' => 0,
        'ifmark' => 0,
        'ifshield' => 0,
        'anonymous' => 0,
        'dig' => 0,
        'fight' => 0,
        'ptable' => 0,
        'ifmagic' => 0,
        'ifhide' => 0,
        'inspect' => '',
        'frommob' => 0,
        'tpcstatus' => 0,
        'specialsort' => 0,
        'lastupdate' => 0,
        'unsell' => 0,
        'unhide' => 0
    ];
    $dbm = Db::instance(\Config\Database::$master);
    $tid = $dbm->insert('pw_threads')->cols($threadData)->query();
    if(!$tid){
        echo date('Y-m-d H:i:s') . ' Poster Worker:' . $worker->id . ' 发布失败'.PHP_EOL;
        return false;
    }
    echo date('Y-m-d H:i:s') . ' Poster Worker:' . $worker->id . ' 发布成功 new tid: '.$tid.PHP_EOL;

    $tmsgData = [
        'tid' => $tid,
        'aid' => 0,
        'userip' => '42.93.246.71',
        'ifsign' => 1,
        'buy' => '',
        'ipfrom' => '亚太地区',
        'alterinfo' => '',
        'remindinfo' => '',
        'tags' => '',
        'ifconvert' => 2,
        'ifwordsfb' => 1,
        'content' => $row['content'],
        'form' => '',
        'ifmark' => '',
        'c_from' => '',
        'magic' => '',
        'overprint' => 0
    ];
    $rs = $dbm->insert('pw_tmsgs')->cols($tmsgData)->query();
    if($rs){
        $post_state = 2;
        $sp_domain = $db->select('svalue')
                    ->from('pw_spider_settings')
                    ->where('skey', 'sp_domain')
                    ->single();
        if(strpos($row['content'], $row['author']) OR strpos($row['subject'], $row['author']) OR
            strpos($row['content'], $sp_domain) OR strpos($row['subject'], $sp_domain)
            ){
            $post_state = 88;
        }
        $pud = $db->update('pw_spider')
                ->set('new_tid', $tid)
                ->set('state', $post_state)
                ->set('new_author', $post_author['author'])
                ->set('new_post_time', time())
                ->where('id', $row['id'])
                ->where('tid', $row['tid'])
                ->where('state', 1)
                ->query();
        if(!$pud){
            $dbm->delete('pw_threads')->where('tid', $row['tid'])->query();
            $dbm->delete('pw_tmsgs')->where('tid', $row['tid'])->query();
        }else{
            $mdrs = $dbm->update('pw_memberdata')
                ->set('postnum', ['op'=>'+', 'val'=>1])
                ->set('lastpost', time())
                ->where('uid', $post_author['author_id'])
                ->query();
        }
    }
    Workerman\Lib\Timer::add(0.03, 'post', array($worker), false);
}

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')){
    \Workerman\Worker::runAll();
}
