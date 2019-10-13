<?php

namespace Web\Common;

/**
 * 日志类
 */
class Log
{
	/**
	 * 初始化
	 * @return bool
	 */
	public static function init()
	{
		set_error_handler(array('\Web\Common\Log','error_handler'), E_RECOVERABLE_ERROR | E_USER_ERROR);
	}

	/**
	 * 添加日志
	 * @param string $msg
	 * @return void
	 */
	public static function add($msg)
	{
		$log_dir = dirname(__DIR__) . '/Logs/';
		umask(0);
		// 没有log目录创建log目录
		if(!is_dir($log_dir)){
			if(@mkdir($log_dir, 0755, true) === false){
				return false;
			}
		}
		if(!is_readable($log_dir) || !is_writeable($log_dir)){
			return false;
		}
		$log_file = $log_dir . date('Y-m-d') . '.log';
		file_put_contents($log_file, self::microtime_format() . ' '. posix_getpid(). ' ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
	}

	/**
	 * 计算日期时间，精确到毫秒
	 * @return string
	 */
	private static function microtime_format()
	{
	    list($msec, $sec) = explode(" ", microtime());
	    return date('Y-m-d H:i:s',$sec).'.'.sprintf("%03d", round($msec*1000));
	}

	/**
	 * 错误日志捕捉函数
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return false
	 */
	public static function error_handler($errno, $errstr, $errfile, $errline)
	{
		$err_type_map = array(E_RECOVERABLE_ERROR=>'E_RECOVERABLE_ERROR',E_USER_ERROR=>'E_USER_ERROR',E_USER_WARNING=>'E_USER_WARNING');
		
		switch($errno){
			case E_RECOVERABLE_ERROR:
			case E_USER_ERROR:
			case E_USER_WARNING:
				$msg = $err_type_map[$errno].' '.$errstr.PHP_EOL.'File:'.$errfile.'  Line:'.$errline;
				self::add($msg);
				// trigger_error($errstr);
				throw new \Exception($msg, self::CATCHABLE_ERROR);
				break;
			default:
				return false;
		}
	}
}
