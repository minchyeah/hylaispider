<?php

namespace Timer;

use \Library\Db;
use \Library\DbConnection;
use \Library\Huobi;
use \Library\Log;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * 定时器基类
 * @author Minch<yeah@minch.me>
 * @since 2019-01-27
 */
abstract class Base
{
    /**
     * 定时器实例
     * @var array
     */
    protected static $instance = array();
    
    /**
     * 异步连接实例
     * @var \Workerman\Connection\AsyncTcpConnection
     */
    protected $conn = null;
    
    /**
     * 数据库连接
     * @var \Library\DbConnection
     */
    protected $db;
    
    /**
     * 网关地址
     * @var array
     */
    protected $gateway = array();
    
    /**
     * 正在进行的业务处理
     * @var array
     */
    public static $calling = array();
    
    /**
     * 网关签名密钥
     * @var string
     */
    protected $sign;
    
    /**
     * 变量共享组件
     * @var GlobalDataClient
     */
    protected $globaldata = null;

    /**
     * 火币接口
     * var Huobi
     */
    protected $huobi = null;

    /**
     * 构造函数
     */
    protected function __construct()
    {
        // 共享组件初始化
        $this->globaldata = \GlobalData\Client::getInstance(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
        // 链接业务网关
        $this->sign = \Config\Timer::$gateway_sign;
        $this->gateway = 'tcp://' . \Config\Gateway::$address . ':' . \Config\Gateway::$port;
        $this->conn = new AsyncTcpConnection($this->gateway);
        $this->conn->connect();
        $this->huobi = Huobi::getInstance();
        $this->db = Db::instance(\Config\Database::$master);
    }

    /**
     * 获取定时器实例
     * @param string $name
     * @param TimerWorker $worker
     * @return multitype:
     */
    public static function getInstance($name)
    {
        if(!isset(self::$instance[$name]) or !self::$instance[$name]){
            self::$instance[$name] = new $name();
        }
        return self::$instance[$name];
    }

    /**
     * 请求业务处理
     * @param string $business 业务名,如果 Order\Timeout
     * @param array $params 参数
     * @return boolean
     */
    protected function call($class, $params)
    {
        $now = time();
        // 请求业务处理参数
        $dataString = json_encode(array('class'=>$class,'method'=>'run','params'=>$params,'client'=>'timer','sign'=>md5($class . 'run' . json_encode($params) . $this->sign)));
        // 判断业务是否正在处理中
        $call_id = md5($dataString);
        if(!$this->globaldata->add($call_id, $now)){
            $last_time = $this->globaldata->$call_id;
            // 判断是否超时(5分钟)
            if(($now - $last_time) < 300){ // 未超时当作重复请求处理
                //$this->log('ERROR 重复请求业务处理 '.$dataString);
                return false;
            }
            $this->log('WARNING 执行业务超时,重新请求业务处理 '.$dataString);
        }
        
        if(!$this->conn){
            // 建立异步链接
            $this->conn = new AsyncTcpConnection($this->gateway);
            $this->conn->connect();
        }
        // 发送数据
        $this->conn->send($dataString . "\n");
        // 异步获得结果
        $this->conn->onMessage = function ($conn, $result)
        {
            // 处理结果
            $res = explode("\n", trim($result));
            foreach($res as $re){
                // $this->log('返回内容 '.print_r($re, true));
                $val = json_decode($re, true);
                $call_id = $val['call_id'];
                // 解除正在进行中的任务
                unset($this->globaldata->$call_id);
            }
        };
    }
    
    /**
     * 记录日志
     * @param string $msg
     * @return void
     */
    protected function log($msg = '')
    {
        return Log::add($msg);
    }

    /**
     * 业务触发器，所有定时业务都需要实现此方法
     */
    abstract public function trigger();

    /**
     * 获取定时业务触发周期
     */
    protected function getInterval()
    {
        $key = substr(get_class($this), 6);
        $module = \Config\Timer::$modules[$key];
        if(is_numeric($module) && $module > 0){
            return $module;
        }elseif(isset($module['interval']) && is_numeric($module['interval']) && $module['interval'] > 0){
            return $module['interval'];
        }
    }

    /**
     * 抢资源锁
     * @return boolean
     */
    protected function getlock()
    {
        $now = time();
        $gkey = get_class($this);
        $gdata = $ndata = $this->globaldata->$gkey;
        $pid = posix_getpid();
        $nexttime = isset($gdata['interval']) && $gdata['last_time'] ? $gdata['interval'] + $gdata['last_time'] : $now;
        if(isset($gdata['end_time']) && $gdata['end_time'] > 0 && $nexttime > $gdata['end_time']){
            $nexttime =  $gdata['end_time'];
        }
        
        // echo 'getlock_' . $pid . '_' . $nexttime . ':' . $now . '_' . $gkey . PHP_EOL;
        // 未到执行时间
        if($now < $nexttime){
            $this->wait($nexttime - $now);
            return false;
        }
        // 执行超时(5分钟),释放资源
        if(($now - $nexttime) > 300){
            $this->log('定时触发任务超时释放资源 '.$gkey);
            $ndata['pid'] = 0;
            $ndata['last_time'] = $now;
            $this->globaldata->$gkey = $ndata;
            $this->wait();
            return false;
        }
        if($gdata['pid'] != 0 || $gdata['pid'] == $pid){
            $this->wait();
            return false;
        }
        if(isset($gdata['once']) && $gdata['once'] && $gdata['last_time']>0){
            return false;
        }
        $ndata['last_time'] = $now;
        $ndata['pid'] = $pid;
        // 抢资源锁
        if(!$this->globaldata->cas($gkey, $gdata, $ndata)){
            $this->wait();
            return false;
        }
        unset($gkey, $gdata, $ndata, $pid, $nexttime);
        return true;
    }

    /**
     * 释放资源
     * @return boolean
     */
    protected function unlock()
    {
        $key = get_class($this);
        $gdata = $ndata = $this->globaldata->$key;
        if($gdata['pid'] == 0){
            return true;
        }
        // 只能本进程解锁
        $pid = posix_getpid();
        if($pid != $gdata['pid']){
            return false;
        }
        $ndata['interval'] = $this->getInterval();
        $ndata['last_time'] = time();
        $ndata['pid'] = 0;
        // 释放资源锁
        if(!$this->globaldata->cas($key, $gdata, $ndata)){
            return false;
        }
        unset($key, $gdata, $ndata, $module, $module_key);
    }

    /**
     * 等待资源释放
     */
    protected function wait($second = 0)
    {
        $continue = true;
        if($second>0){
            $interval = $second;
            $key = get_class($this);
            $gdata = $ndata = $this->globaldata->$key;
            $ndata['interval'] = $interval;
            if(!$this->globaldata->cas($key, $gdata, $ndata)){
                return $this->wait($second);
            }
            unset($key, $gdata, $ndata);
        }else{
            $now = time();
            $key = get_class($this);
            $gdata = $this->globaldata->$key;
            $interval = isset($gdata['interval']) ? $gdata['interval'] : 0;
            if(isset($gdata['end_time']) && $gdata['end_time'] != 0){
                if($gdata['end_time'] < $now){
                    $continue = false;
                }elseif (($now + $interval) > $gdata['end_time']){
                    $interval = $gdata['end_time'] - $now;
                }
            }
            if(isset($gdata['once']) && $gdata['once'] && $gdata['last_time'] > 0){
                $continue = false;
            }
            unset($key, $gdata);
        }
        if($continue && $interval > 0){
            \Workerman\Lib\Timer::add($interval, array(self::getInstance(get_class($this)),'trigger'), array(), false);
        }
        unset($second, $interval);
    }
}