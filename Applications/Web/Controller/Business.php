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
    public function dump($key)
    {
        echo '<pre>';
        var_dump($this->gdata($key));
    }

    /**
     * 调用更新币种业务
     * @param  string $currency [description]
     * @return 
     */
    public function reprice($currency)
    {
        $params = ['currency'=>$currency];
        return $this->business('Business\Account\Balance', 'reprice', $params);
    }

    /**
     * 获取交易对订单
     * @param  string $symbol 交易对
     * @return 
     */
    public function orders($symbol)
    {
        $params = ['symbol'=>$symbol];
        if(isset($_GET['start']) && strtotime($_GET['start'])){
            $params['start'] = $_GET['start'];
        }
        if(isset($_GET['end']) && strtotime($_GET['end'])){
            $params['end'] = $_GET['end'];
        }
        return $this->business('Business\Account\Orders', 'run', $params);
    }

    /**
     * 获取K线数据
     * @param  string $symbol 交易对
     * @param  string $period 周期
     * @return
     */
    public function kline($symbol, $period)
    {
        $params = ['symbol'=>$symbol, 'period'=>$period];
        if(isset($_GET['size']) && intval($_GET['size'])){
            $params['size'] = intval($_GET['size']);
        }else{
            $params['size'] = 100;
        }
        return $this->business('Business\Market\Kline', 'run', $params);
    }
}