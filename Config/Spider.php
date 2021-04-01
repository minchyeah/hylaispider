<?php

namespace Config;

/**
 * 
 * @author minch
 */
class Spider
{
    public static $name = 'SpiderWorker';
    public static $tasknum = 5;
    public static $domains = array(
        'www.pw.com'
    );
    public static $scan_urls = array(
        'http://www.hyl999.vip/',   // 随便定义一个入口，要不然会报没有入口url错误，但是这里其实没用
    );
    public static $list_url_regexes = array(
        '/\/thread.php\?fid=2&search=all&page=(\d+)$/',   // 列表页
        '/\/thread\/2\/(\d+).html$/',   // 列表页
    );
    public static $content_url_regexes = array(
        '/\/read.php\?tid=(\d+)(&fpage=(\d+))*$/',
        '/\/html\/2\/(\d+).html$/',   // 列表页
    );

    public static $list_page_regexes = '/&page=(\d+)/'; // 获取列表分页
    public static $content_id_regexes = '/tid=(\d+)/'; // 获取帖子ID
    public static $href_type = 'absolute';  // 地址类型,relative:相对路径，absolute:绝对路径

    public static $fields = array(
        // 时间
        array(
            'name' => 'post_time',
            'selector' => '//div[contains(@id,"pw_content")]//div[contains(@class,"tipTop")]//span[2]//@title',
            'required' => true,
        ),
        // 作者
        array(
            'name' => 'author',
            'selector' => '//table[contains(@class,"floot")]//div[contains(@class,"readName")]//a',
            'required' => true,
        ),
        // 标题
        array(
            'name' => 'subject',
            'selector' => '//h1[contains(@id,"subject_tpc")]',
            'required' => true,
            'filter' => '/<a[\s]+([^>]+)>((?:.(?!\<\/a\>))*.)<\/a>/',
        ),
        // 内容
        array(
            'name' => 'content',
            'selector' => '//div[contains(@class,"tpc_content")]//div[contains(@id,"read_tpc")]',
            'required' => true,
            'filter' => '/<div[\s]+([^>]+)>((?:.(?!\<\/div\>))*.)重新编辑((?:.(?!\<\/div\>))*.)<\/div>/',
        ),
    );
}
