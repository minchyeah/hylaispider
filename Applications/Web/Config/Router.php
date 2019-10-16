<?php

namespace Web\Config;

/**
 * Config Router Map.
 */
class Router
{
    public static $get = [
        '/' => 'Index@index',
        '/login' => 'Index@login',
        '/dologin' => 'Index@doLogin',
        '/balance' => 'Balance@index',
        '/balance/data' => 'Balance@data',
        '/balance/(\w+)' => 'Balance@detail',
        '/business/(\w+)/(\w+)/(\w+)' => 'Business@call',
        '/check/(\w+)/(\w+)/(\w+)' => 'Trade@check',
        '/gdata/(\w+)' => 'Business@gdata',
        '/margin' => 'Margin@index',
        '/margin/(\w+)' => 'Margin@index',
        '/symbol' => 'Symbol@index',
    ];

    public static $post = [];
}
