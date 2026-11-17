<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use PmntCode\Mvc\Container\Container;
use PmntCode\Mvc\Contracts\ViewInterface;
use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Routing\Router;
use PmntCode\Mvc\View\View;

$container = new Container();
$container->instance(ViewInterface::class, new View((require dirname(__DIR__) . '/config/views.php')['path']));
$router = new Router($container);
require dirname(__DIR__) . '/routes/web.php';
$request = Request::fromGlobals();
$router->dispatch($request)->send($request->method === 'HEAD');
