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
        '/business/(\w+)/(\w+)/(\w+)' => 'Business@call',
        '/check/(\w+)/(\w+)/(\w+)' => 'Trade@check',
        '/dump/(\w+)' => 'Business@dump',
        '/kline/(\w+)/(\w+)' => 'Business@kline',
        '/login' => 'Auth@login',
        '/logout' => 'Auth@logout',
        '/margin' => 'Margin@index',
        '/margin/(\w+)' => 'Margin@index',
        '/orders/(\w+)' => 'Business@orders',
        '/reprice/(\w+)' => 'Business@reprice',
        '/symbol' => 'Symbol@index',
    ];

    public static $post = [
        '/dologin' => 'Auth@doLogin',
    ];
}
