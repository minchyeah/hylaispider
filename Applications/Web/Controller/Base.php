<?php

namespace Web\Controller;

use \Library\Db;
use \Web\Common\Log;
use \Web\Common\Template;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Protocols\Http;

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
     * 异步连接实例
     * @var \Workerman\Connection\AsyncTcpConnection
     */
    protected $conn = null;

    /**
     * 构造函数
     */
	public function __construct()
	{
        Http::sessionStart();
		$this->view = new Template();
		$this->db = Db::instance(\Config\Database::$master);
		$this->globaldata = \GlobalData\Client::getInstance(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
	}

    /**
     * check user is login
     * @return boolean
     */
    protected function isLogin()
    {
        return \Web\Config\Auth::$username == $_SESSION['auth'];
    }

    /**
     * 验证登录信息
     * @param  string $username 用户名
     * @param  string $password 密码
     * @return boolean
     */
    protected function checkLogin($username, $password)
    {
        if(\Web\Config\Auth::$username == $username && \Web\Config\Auth::$encrypted == md5(md5($password).\Web\Config\Auth::$salt)){
            $_SESSION['auth'] = $username;
            echo 'login success!';
            return true;
        }else{
            echo 'login fail!';
            return false;
        }
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

}