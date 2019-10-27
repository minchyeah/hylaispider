<?php

namespace Web\Config;

/**
 * Config Router Map.
 */
class Router
{
    public static $get = [
        '/' => 'Index@index',
        '/index' => 'Index@index',
        '/index\.html' => 'Index@index',
        '/balance' => 'Balance@index',
        '/balance/data' => 'Balance@data',
        '/collections' => 'Collections@index',
        '/collections/data' => 'Collections@data',
        '/check/(\w+)/(\w+)/(\w+)' => 'Trade@check',
        '/dump/(\w+)' => 'Business@dump',
        '/kline/(\w+)/(\w+)' => 'Business@kline',
        '/login' => 'Auth@login',
        '/logout' => 'Auth@logout',
        '/margin' => 'Margin@index',
        '/margin/(\w+)' => 'Margin@index',
        '/orders/(\w+)' => 'Business@orders',
        '/reprice/(\w+)' => 'Business@reprice',
        '/setting' => 'Setting@index',
        '/users' => 'users@index',
    ];

    public static $post = [
        '/dologin' => 'Auth@doLogin',
    ];
}
