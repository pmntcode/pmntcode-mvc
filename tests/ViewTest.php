<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PmntCode\Mvc\View\View;
use PmntCode\Mvc\Container\Container;
use PmntCode\Mvc\Contracts\ViewInterface;
use PmntCode\Mvc\Routing\Router;
use PmntCode\Mvc\Http\Request;
use App\Controllers\HomeController;

final class ViewTest extends TestCase
{
    private function views(): View
    {
        $config = require dirname(__DIR__) . '/config/views.php';

        return new View($config['path']);
    }

    public function testConfiguredPathPointsToAppViews(): void
    {
        $config = require dirname(__DIR__) . '/config/views.php';

        self::assertSame(
            realpath(dirname(__DIR__) . '/app/Views'),
            realpath($config['path'])
        );

        self::assertDirectoryExists($config['path']);
    }

    public function testLayoutAndPartial(): void
    {
        $response = $this->views()->render(
            'home.index',
            ['title' => 'Teste'],
            'layouts.app'
        );

        self::assertSame(200, $response->status);

        self::assertStringContainsString(
            '<title>Teste · PmntCode MVC</title>',
            $response->body
        );

        self::assertStringContainsString(
            '<main id="conteudo">',
            $response->body
        );

        self::assertStringContainsString(
            '<footer class="site-footer">',
            $response->body
        );
    }

    public function testTraversalRejected(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->views()->partial('../secret');
    }

    public function testControllerDependencyInjection(): void
    {
        $container = new Container();

        $container->instance(
            ViewInterface::class,
            $this->views()
        );

        $router = new Router($container);

        $router->get(
            '/',
            [HomeController::class, 'index']
        );

        $response = $router->dispatch(
            new Request('GET', '/')
        );

        self::assertSame(200, $response->status);

        self::assertStringContainsString(
            '<main id="conteudo">',
            $response->body
        );

        self::assertStringContainsString(
            '<h1>',
            $response->body
        );
    }
}
