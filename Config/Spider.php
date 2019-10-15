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
            'selector' => "//span[contains(@class,'breadcrumbs-target')]",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "category",
            'selector' => "//p[contains(@class,'breadcrumbs-path')]//a",
            'required' => true,
        ),
        // 图片
        array(
            'name' => "images",
            'selector' => "//div[contains(@class,'goodsDetail-gallery')]//ul//li//@goods-img",
            'required' => true,
            'repeated' => true
        ),
    );
}
