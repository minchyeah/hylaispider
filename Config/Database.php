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
	 * 采集数据库配置
	 * @var array
	 */
	public static $default = array(
		'host'		=> '172.31.0.1',
		'port'		=> '3306',
		'user'		=> 'root',
		'password'	=> '111111',
		'dbname'	=> 'www_pw_com',
		'charset'	=> 'utf8',
	);
	
	/**
	 * 发布数据库配置
	 * @var array
	 */
	public static $master = array(
		'host'		=> '172.31.0.1',
		'port'		=> '3306',
		'user'		=> 'root',
		'password'	=> '111111',
		'dbname'	=> 'www_pw_com',
		'charset'	=> 'utf8',
	);
}