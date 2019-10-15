<?php

namespace Config;

/**
 * 
 * @author minch
 */
class Spider
{
    public static $name = 'ZhongHuaSuan';
    public static $tasknum = 3;
    public static $domains = array(
        'www.zhonghuasuan.com'
    );
    public static $scan_urls = array(
        "https://www.zhonghuasuan.com",   // 随便定义一个入口，要不然会报没有入口url错误，但是这里其实没用
    );
    public static $list_url_regexes = array(
        "https://list.zhonghuasuan.com",         // 列表页
        "https://list.zhonghuasuan.com/cat-0-0-(\d+).html",   // 列表页
    );
    public static $content_url_regexes = array(
        "https://detail.zhonghuasuan.com/\d+.html",
    );
    public static $fields = array(
        // 标题
        array(
            'name' => "title",
            'selector' => "//h1[contains(@class,'headtext')]",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "category",
            'selector' => "//div[contains(@class,'relation_mdd')]//a",
            'required' => true,
        ),
        // 图片
        array(
            'name' => "images",
            'selector' => "//li[contains(@class,'time')]",
            'required' => true,
            'repeated' => true
        ),
    );
}
