<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Controllers\HomeController;
use PmntCode\Mvc\Http\Response;
use PmntCode\Mvc\Routing\Router;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/hello/{name}', fn($request, $name) => Response::json(['hello' => $name]));
$router->group('/api/v1', function (Router $router): void {
    $idRoute = $router->get('/users/{id}', [UserController::class, 'show']);
    $router->where($idRoute, 'id', '[0-9]+');
    $router->name($idRoute, 'users.show');
    $router->post('/echo', fn($request) => Response::json(['body' => $request->body], 201));
});
