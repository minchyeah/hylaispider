<?php

namespace Config;

class Gateway
{
    /**
     * 网关签名,用于验证客户连接的合法性
     * @var array array(client=>sign,client=>sign,...)
     */
    public static $client_sign = array(
        'business' => '111111',
        'web'      => '222222',
        'timer'    => '333333'
    );
    
    /**
     * 名称，status方便查看
     * @var string
     */
    public static $worker_name = 'GatewayWorker';
    
    /**
     * 进程数
     * @var string
     */
    public static $worker_count = 1;
    
    /**
     * 网关协议
     * @var string
     */
    public static $protocol = 'Text';
    
    /**
     * 网关监听的ip地址，分布式部署时使用内网ip
     * @var string
     */
    public static $address = '127.0.0.1';
    
    /**
     * 网关监听端口
     * @var number
     */
    public static $port = 8292;
    
    /**
     * 内部通讯起始端口，假如$worker_count=4，起始端口为4000,则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
     * @var number
     */
    public static $inner_start_port = 2900;
    
    /**
     * 服务注册地址ip地址，分布式部署时使用内网ip
     * @var string
     */
    public static $register_address = '127.0.0.1';
    
    /**
     * 服务注册端口
     * @var number
     */
    public static $register_port = 10238;
}
