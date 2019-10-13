<?php

namespace Web\Controller;

class Business extends Base
{
	/**
     * 业务命名空间白名单
     * @var array
     */
	protected $namespaces = ['Account','Common','Market','Trade'];

    /**
     * 业务类名白名单
     * @var array
     */
	protected $classes = ['Balance','Orders','Symbols','Kline','CleanKline'];

    /**
     * 请求业务
     * @param string $namespace 命名空间
     * @param string $class 业务名
     * @param array $method 业务方法
     */
	public function call($namespace,$class,$method)
	{
		$namespace = ucfirst($namespace);
		$class = ucfirst($class);
		if(in_array($namespace, $this->namespaces) && in_array($class, $this->classes)){
			return $this->business('Business\\'.$namespace.'\\'.$class, $method, $_GET);
		}
		return $this->notfound();
	}

    /**
     * 打印共享数据
     * @param string $key 键名
     */
    public function gdata($key)
    {
        echo '<pre>';
        var_dump($this->globaldata->{$key});
    }
}