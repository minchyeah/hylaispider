<?php

namespace Web\Controller;

use \Library\Db;
use \Library\Huobi;
use \Web\Common\Log;
use \Web\Common\Template;
use \Workerman\Connection\AsyncTcpConnection;

class Base
{
    /**
     * 数据库连接
     * @var \Library\DbConnection
     */
    protected $db;

    /**
     * 变量共享组件
     * @var GlobalData\Client
     */
    protected $globaldata = null;

    /**
     * 火币API类库
     * @var Library\Huobi
     */
    protected $huobi = null;

    /**
     * 模板类库
     * @var Web\Common\Template
     */
    protected $view = null;

    /**
     * 网关签名密钥
     * @var string
     */
    protected $sign;

    /**
     * 网关地址
     * @var array
     */
    protected $gateway = array();

    /**
     * 异步连接实例
     * @var \Workerman\Connection\AsyncTcpConnection
     */
    protected $conn = null;

    /**
     * 构造函数
     */
	public function __construct()
	{
		$this->view = new Template();
		$this->db = Db::instance(\Config\Database::$master);
		$this->globaldata = \GlobalData\Client::getInstance(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
        $this->huobi = Huobi::getInstance();
        $this->sign = \Config\Timer::$gateway_sign;
        $this->gateway = 'tcp://' . \Config\Gateway::$address . ':' . \Config\Gateway::$port;
        $this->conn = new AsyncTcpConnection($this->gateway);
        $this->conn->connect();
	}

    /**
     * 设置单个模板变量
     * @param string $key
     * @param string $value
     */
	protected function set($key, $value)
	{
		$this->view->setVar($key, $value);
	}

    /**
     * 设置多个模板变量
     * @param array $vars
     */
	protected function sets($vars)
	{
		$this->view->setVars($vars);
	}

    /**
     * 渲染模板输出
     * @param string $tpl
     */
	protected function render($tpl)
	{
		$this->view->setFile(dirname(__DIR__).'/View/'.$tpl);
		echo $this->view->render();
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
     * 记录日志
     * @param string $msg
     * @return void
     */
    protected function log($msg = '')
    {
        return Log::add($msg);
    }

    /**
     * 404页面
     */
    public function notfound()
    {
        header('HTTP/1.1 404 Not Found');
        $this->render('404.html');
    }

    /**
     * 请求业务处理
     * @param string $class  Business业务名,如果 Order\Timeout
     * @param string $method 方法
     * @param array $params 参数
     * @return boolean
     */
    protected function business($class, $method, $params)
    {
        // 请求业务处理参数
        $dataString = json_encode(array('class'=>$class,'method'=>$method,'params'=>$params,'client'=>'timer','sign'=>md5($class . $method . json_encode($params) . $this->sign)));
        // 判断业务是否正在处理中
        $call_id = md5($dataString);
        if(!$this->globaldata->add($call_id, time())){
            return false;
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
                $val = json_decode($re, true);
                $call_id = $val['call_id'];
                // 解除正在进行中的任务
                unset($this->globaldata->$call_id);
            }
        };
    }
}