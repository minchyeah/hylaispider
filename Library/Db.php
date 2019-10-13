<?php
namespace Library;
/**
 * 数据库类
 */
class Db
{
    /**
     * 实例数组
     * @var array
     */
    protected static $instance = array();
    
    /**
     * 获取实例
     * @param array $config array('host'=>'127.0.0.1','port'=>'3306','user'=>'root','password'=>'111111','dbname'=>'mydbname','charset'=>'utf8')
     * @throws \Exception
     */
    public static function instance($config)
    {
        $config_name = md5($config['host'] . $config['port'] . $config['user'] . $config['password'] . $config['dbname'] . $config['charset']);
        
        if(empty(self::$instance[$config_name]))
        {
            self::$instance[$config_name] = new DbConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['dbname'], $config['charset']);
        }
        return self::$instance[$config_name];
    }
    
    /**
     * 关闭数据库实例
     * @param string $config
     */
    public static function close($config)
    {
        $config_name = md5($config['host'] . $config['port'] . $config['user'] . $config['password'] . $config['dbname'] . $config['charset']);
        if(isset(self::$instance[$config_name]))
        {
            self::$instance[$config_name]->closeConnection();
            self::$instance[$config_name] = null;
        }
    }
    
    /**
     * 关闭所有数据库实例
     */
    public static function closeAll()
    {
        foreach(self::$instance as $connection)
        {
            $connection->closeConnection();
        }
        self::$instance = array();
    }
}
