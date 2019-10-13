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

// 自动加载类
require_once dirname(__DIR__) . '/loader.php';

// TimerWorker
$worker = new \Workerman\Worker();
$worker->count = Config\Timer::$worker_count;
$worker->name = Config\Timer::$worker_name;
$worker->onWorkerStart = function () use ($worker)
{
    // 延迟1秒，等待网关、共享数据组件等初始化完成
    sleep(1);
    $modules = Config\Timer::$modules;
    $globaldata = new GlobalData\Client(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
    foreach($modules as $module=>$interval){
        $timer = Timer\Base::getInstance($className = 'Timer\\' . $module);
        if(is_numeric($interval) && $interval > 0){
            $globaldata->add($className, array('interval'=>$interval, 'last_time'=>0, 'pid'=>0));
            // 触发任务
            Workerman\Lib\Timer::add(0.01, array($timer,'trigger'), array(), false);
        }elseif(is_array($interval)){
            $args = $interval;
            unset($interval);
            $interval = $start_time = $end_time = $last_time = 0;
            $now = time();
            $gdata = array(
                    'last_time'=>$last_time,
                    'pid'=>0
                );
            if(isset($args['interval']) && intval($args['interval'])) {
                $gdata['interval'] = intval($args['interval']);
            }
            if(isset($args['start']) && strtotime($args['start'])) {
                $gdata['start_time'] = strtotime($args['start']);
                $start_time = $gdata['start_time'];
            }
            if(isset($args['end']) && strtotime($args['end'])) {
                $gdata['end_time'] = strtotime($args['end']);
                $end_time = $gdata['end_time'];
            }
            $trigger_time = $start_time - $now;
            // 一次性业务过时不添加定时
            if(isset($args['once']) && $args['once']){
                $gdata['once'] = 1;
                if($trigger_time < 0){
                    continue;
                }
            }
            if ($trigger_time <= 0) {
                $trigger_time = 0.01;
                $gdata['last_time'] = $now;
            }
            if($end_time == 0 || $end_time > $now){
                $globaldata->add($className, $gdata);
                Workerman\Lib\Timer::add($trigger_time, array($timer,'trigger'), array(), false);
            }
        }
    }
};
// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')){
    \Workerman\Worker::runAll();
}
