<?php

use \Web\Common\Log;
use \Web\Common\Router;

Log::init();

$router = new Router();
$router->setBasePath('/');
$router->setNamespace('\Web\Controller');

$router->set404('Index@notfound');

array_walk(\Web\Config\Router::$get, function($value, $key) use($router){
	$router->get($key, $value);
});

$router->run();
