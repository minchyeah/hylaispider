<?php

use \Web\Common\Log;
use \Web\Common\Router;

Log::init();

$router = Router::getInstance();
$router->setBasePath('/');
$router->setNamespace('\Web\Controller');

$router->set404('Auth@notfound');

array_walk(\Web\Config\Router::$get, function($value, $key) use($router){
	$router->get($key, $value);
});

array_walk(\Web\Config\Router::$post, function($value, $key) use($router){
	$router->post($key, $value);
});

$router->run();
