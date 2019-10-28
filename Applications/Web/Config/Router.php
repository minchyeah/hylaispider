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
        '/collections' => 'Collections@index',
        '/collections/data' => 'Collections@data',
        '/dump/(\w+)' => 'Business@dump',
        '/export/(\w+)' => 'Business@export',
        '/login' => 'Auth@login',
        '/logout' => 'Auth@logout',
        '/setting' => 'Setting@index',
        '/users' => 'Users@index',
        '/users/data' => 'Users@data',
    ];

    public static $post = [
        '/dologin' => 'Auth@doLogin',
        '/setting' => 'Setting@save',
        '/users/save' => 'Users@save',
    ];
}
