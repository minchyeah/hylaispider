<?php

namespace Config;

/**
 * 
 * @author minch
 */
class Spider
{
    public static $name = 'pw';
    public static $tasknum = 3;
    public static $domains = array(
        'www.pw.com'
    );
    public static $scan_urls = array(
        'http://www.pw.com/',   // 随便定义一个入口，要不然会报没有入口url错误，但是这里其实没用
    );
    public static $list_url_regexes = array(
        '/http:\/\/www.pw.com/',         // 列表页
        '/http:\/\/www.pw.com\/thread.php\?fid=(\d+)&page=(\d+)/',   // 列表页
    );
    public static $content_url_regexes = array(
        '/http:\/\/www.pw.com\/read.php\?tid=(\d+)/',
    );
    public static $fields = array(
        // 标题
        array(
            'name' => 'title',
            'selector' => '/h1[contains(@id,"subject-tpc")]//',
            'required' => false,
        ),
        // 分类
        array(
            'name' => 'author',
            'selector' => '//div[contains(@class,"readName")]//a',
            'required' => false,
        ),
        // 图片
        array(
            'name' => 'content',
            'selector' => '//div[contains(@class,"tpc_content")]//div[contains(@id,"read_tpc")]/*',
            'required' => true,
            'repeated' => true
        ),
    );
}
