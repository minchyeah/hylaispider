<?php

/**
 * 业务处理器配置
 */
namespace Config;

class Business
{
    /**
     * 进程名
     *
     * @var string
     */
    public static $worker_name = 'BusinessWorker';
    
    /**
     * 开启进程数
     *
     * @var number
     */
    public static $worker_count = 4;

}
