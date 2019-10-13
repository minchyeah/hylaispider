<?php 
namespace Config;

/**
 * MySQL数据库配置
 * @author minch<yeah@minch.me>
 */
class Database
{
	/**
	 * 数据库的一个实例配置，则使用时像下面这样使用
	 * $user_array = Db::instance('one_demo')->select('name,age')->from('user')->where('age>12')->query();
	 * 等价于
	 * $user_array = Db::instance('one_demo')->query('SELECT `name`,`age` FROM `one_demo` WHERE `age`>12');
	 * @var array
	 */
	
	/**
	 * 主库配置(读写)
	 * @var array
	 */
	public static $master = array(
		'host'		=> '172.31.0.2',
		'port'		=> '3306',
		'user'		=> 'root',
		'password'	=> '111111',
		'dbname'	=> 'bitting',
		'charset'	=> 'utf8',
	);
	
	/**
	 * 从库配置(只读)
	 * @var array
	 */
	public static $slave = array(
		'host'		=> '172.31.0.2',
		'port'		=> '3306',
		'user'		=> 'root',
		'password'	=> '111111',
		'dbname'	=> 'bitting',
		'charset'	=> 'utf8',
	);

	public static $mysql = array(
       // 数据库类型
       'type'     => 'mysql',
       // 主机地址
       'hostname' => '127.0.0.1',
       // 端口
       'port' => '3306',
       // 用户名
       'username' => 'root',
       // 密码
       'password' => '111111',
       // 数据库名
       'database' => 'bitting',
       // 数据库编码默认采用utf8
       'charset'  => 'utf8',
       // 数据库表前缀
       'prefix'   => '',
       // 数据库调试模式
       'debug'    => true
	);
}