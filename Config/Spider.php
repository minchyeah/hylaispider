<?php

namespace Config;

/**
 * 
 * @author minch
 */
class Spider
{
    public static $name = 'SpiderWorker';
    public static $tasknum = 3;
    public static $domains = array(
        'www.pw.com'
    );
    public static $scan_urls = array(
        'http://www.hyl999.vip/',   // 随便定义一个入口，要不然会报没有入口url错误，但是这里其实没用
    );
    public static $list_url_regexes = array(
        '/\/thread.php\?fid=(\d+)(&page=(\d+))*$/',   // 列表页
    );
    public static $content_url_regexes = array(
        '/\/read.php\?tid=(\d+)(&fpage=(\d+))*$/',
    );

    public static $fields = array(
        // 标题
        array(
            'name' => 'subject',
            'selector' => '//h1[contains(@id,"subject_tpc")]',
            'required' => true,
            'filter' => '/<a[\s]+([^>]+)>((?:.(?!\<\/a\>))*.)<\/a>/',
        ),
        // 作者
        array(
            'name' => 'author',
            'selector' => '//table[contains(@class,"floot")]//div[contains(@class,"readName")]//a',
            'required' => true,
        ),
        // 时间
        array(
            'name' => 'post_time',
            'selector' => '//div[contains(@id,"pw_content")]//div[contains(@class,"tipTop")]//span[contains(@class,"mr5")]//@title',
            'required' => true,
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
