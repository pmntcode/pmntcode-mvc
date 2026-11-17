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
    private function views(): View { return new View((require dirname(__DIR__) . '/config/views.php')['path']); }
    public function testConfiguredPathPointsToAppViews(): void
    {
        $config = require dirname(__DIR__) . '/config/views.php';
        self::assertSame(realpath(dirname(__DIR__) . '/app/Views'), realpath($config['path']));
        self::assertDirectoryExists($config['path']);
    }
    public function testLayoutAndPartial(): void
    {
        $response = $this->views()->render('home.index', ['title' => 'Teste', 'name' => '<script>alert(1)</script>'], 'layouts.app');
        self::assertSame(200, $response->status);
        self::assertStringContainsString('<title>Teste</title>', $response->body);
        self::assertStringContainsString('&lt;script&gt;', $response->body);
        self::assertStringNotContainsString('<script>', $response->body);
        self::assertStringContainsString('<footer>', $response->body);
    }
    public function testTraversalRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->views()->partial('../secret');
    }
    public function testControllerDependencyInjection(): void
    {
        $container = new Container();
        $container->instance(ViewInterface::class, $this->views());
        $router = new Router($container);
        $router->get('/', [HomeController::class, 'index']);
        $response = $router->dispatch(new Request('GET', '/'));
        self::assertSame(200, $response->status);
        self::assertStringContainsString('Views e layouts funcionando', $response->body);
    }
}
