<?php

namespace Business;

use \Library\Db;
use \Library\DbConnection;
use \Library\Huobi;
use \Library\Log;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * 业务处理基类
 * @author Minch<yeah@minch.me>
 * @since 2019-01-27
 */
abstract class Base
{
    /**
     * 业务处理实例
     * @var array
     */
    protected static $instance = array();
    
    /**
     * 数据库连接
     * @var DbConnection
     */
    protected $db;
    
    /**
     * 网关地址
     * @var string
     */
    protected $gateway_address = '';
    
    /**
     * 网关签名
     * @var string
     */
    protected $gateway_sign = '';
    
    /**
     * 网关链接
     * @var AsyncTcpConnection
     */
    protected $gateway_conn = null;
    
    /**
     * 错误代码
     * @var number
     */
    protected $errCode = 0;
    
    /**
     * 错误信息
     * @var string
     */
    protected $errMsg = '';
    
    /**
     * 异步请求 array(call_id=>array(callback,params))
     * @var array
     */
    protected static $async_calling = array();
    
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
    public function __construct()
    {
        $this->globaldata = \GlobalData\Client::getInstance(\Config\GlobalData::$address.':'.\Config\GlobalData::$port);
        $this->gateway_address = 'tcp://'.\Config\Gateway::$address.':'.\Config\Gateway::$port;
        $this->huobi = Huobi::getInstance();
        $this->db = Db::instance(\Config\Database::$master);
    }

    abstract public function run($params);

    /**
     * 获取业务处理器实例
     * @param string $name
     * @return multitype:
     */
    public static function getInstance($name)
    {
        if(!isset(self::$instance[$name]) OR !self::$instance[$name]){
            self::$instance[$name] = new $name();
        }
        return self::$instance[$name];
    }

    /**
     * 业务处理器之间相互调用（同步）
     * @param string $class 类名
     * @param string $method 方法名
     * @param array $params 参数
     * @return boolean
     */
    protected static function call($class, $method, $params)
    {
        $res = call_user_func(array(self::getInstance($class),$method), $params);
        unset($class, $method, $params);
        return $res;
    }

    /**
     * 获取下次触发时间间隔
     * @param number $error_num 错误次数
     * @return number
     */
    protected function getNextTriggerTime($error_num = 0)
    {
        $maps = array('0'=>30,'1'=>180,'2'=>600,'3'=>1800);
        return isset($maps[$error_num]) ? $maps[$error_num] : 0;
    }

    /**
     * 异步请求业务处理
     * @param string $class 业务类名(带命名空间，如：Hulianpay\Webservice\User\GetMoney)
     * @param string $method 业务方法名
     * @param array $params 请求参数
     * @param mix $callback 成功后回调函数
     */
    protected function asyncCall($class, $method, $params, $callback = null)
    {
        $sign = md5($class.$method.json_encode($params).\Config\Gateway::$client_sign['business']);
        // 请求业务处理参数
        $dataString = json_encode(array('class'=>$class, 'method'=>$method,'params'=>$params,'client'=>'business','sign'=>$sign ));
        
        // 判断业务是否正在处理中
        $call_id = md5($dataString);
        if(array_key_exists($call_id, self::$async_calling)){
            return false;
        }
        if( $callback != null ){
            // 添加到业务处理列表
            self::$async_calling[$call_id] = array('callback'=>$callback, 'params'=>$params);
        }
        
        // 建立异步链接
        if(!$this->gateway_conn){
            // 建立异步链接
            $this->gateway_conn = new AsyncTcpConnection($this->gateway_address);
            $this->gateway_conn->connect();
        }
        // 发送数据
        $this->gateway_conn->send($dataString . "\n");
        // 异步获得结果
        $this->gateway_conn->onMessage = function ($conn, $result)
        {
            // 处理结果
            $res = explode("\n", trim($result));
            foreach ($res as $re){
                $val = json_decode($re, true);
                if (!isset($val['call_id']) || !isset(self::$async_calling[$val['call_id']])) continue;
                call_user_func(self::$async_calling[$val['call_id']]['callback'], self::$async_calling[$val['call_id']]['params'], $val);
                // 解除正在进行中的任务
                unset(self::$async_calling[$val['call_id']], $val);
            }
            // 解除正在进行中的任务
            unset($result,$res);
        };
        unset($dataString, $service, $params, $call_id, $conn, $callback);
        return true;
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
     * 获取共享数据变量
     * @param string $key 
     */
    protected function gdata($key)
    {
        return $this->globaldata->$key;
    }

    /**
     * 设置共享数据变量
     * @param string $key 
     * @param mixed $value 
     * @param mixed $expire 
     * @return mixed 
     * @throws \Exception
     */
    protected function gadd($key, $value, $expire = 0)
    {
        return $this->globaldata->add($key, $value, $expire);
    }

    /**
     * 共享数据变量原子操作
     * @param string $key
     * @param mixed $old_value
     * @param mixed $new_value
     */
    protected function gcas($key, $old_value, $new_value)
    {
        return $this->globaldata->cas($key, $old_value, $new_value);
    }

    /**
     * 设置共享数据变量过期时间
     * @param string $key 
     * @param mixed $expire 
     * @return mixed 
     */
    protected function gexpire($key, $expire = 0)
    {
        return $this->globaldata->expire($key, $expire);
    }

    /**
     * 设置共享数据变量
     * @param string $key 
     * @param mixed $value 
     */
    protected function gset($key, $value)
    {
        $this->globaldata->$key = $value;
    }

    /**
     * 销毁共享数据变量
     * @param string $key
     */
    protected function gunset($key)
    {
        unset($this->globaldata->$key);
    }

    /**
     * 设置错误信息
     * @param number $errCode 错误编码(1000:数据不存在或状态异常,2000:数据更新失败)
     * @param string $errMsg 错误信息
     * @return boolean false
     */
    protected function error($errCode = 0, $errMsg = '')
    {
        $this->errCode = $errCode;
        $this->errMsg = $errMsg;
        Log::add('ERROR[' . $errCode . '] ' . $errMsg);
        return false;
    }

    /**
     * 获取错误代码
     * @return number
     */
    public function getErrorCode()
    {
        return $this->errCode;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errMsg;
    }
}