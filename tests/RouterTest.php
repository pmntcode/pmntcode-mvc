<?php
declare(strict_types=1);
namespace Tests;
use PHPUnit\Framework\TestCase;
use PmntCode\Mvc\Routing\Router;
use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Http\Response;
final class RouterTest extends TestCase
{
    public function testStaticRoute(): void { $r = new Router(); $r->get('/ping', fn() => 'pong'); self::assertSame('pong', $r->dispatch(new Request('GET', '/ping'))->body); }
    public function testParameterAndConstraint(): void { $r = new Router(); $id = $r->get('/users/{id}', fn($req, $id) => $id); $r->where($id, 'id', '[0-9]+'); self::assertSame('42', $r->dispatch(new Request('GET', '/users/42'))->body); self::assertSame(404, $r->dispatch(new Request('GET', '/users/no'))->status); }
    public function testOptionalParameter(): void { $r = new Router(); $r->get('/posts/{slug?}', fn($req, $slug = 'all') => $slug); self::assertSame('all', $r->dispatch(new Request('GET', '/posts'))->body); self::assertSame('abc', $r->dispatch(new Request('GET', '/posts/abc'))->body); }
    public function testMethodNotAllowed(): void { $r = new Router(); $r->post('/items', fn() => 'ok'); $response = $r->dispatch(new Request('GET', '/items')); self::assertSame(405, $response->status); self::assertSame('POST', $response->headers['Allow']); }
    public function testMiddleware(): void { $r = new Router(); $r->middleware(fn($req, $next) => new Response('before-' . $next($req)->body)); $r->get('/', fn() => 'after'); self::assertSame('before-after', $r->dispatch(new Request('GET', '/'))->body); }
    public function testGroup(): void { $r = new Router(); $r->group('/api', fn(Router $r) => $r->get('/ping', fn() => 'yes')); self::assertSame('yes', $r->dispatch(new Request('GET', '/api/ping'))->body); }
    public function testJson(): void { $r = new Router(); $r->get('/', fn() => ['ok' => true]); self::assertSame('{"ok":true}', $r->dispatch(new Request('GET', '/'))->body); }
    public function testHeadFallsBackToGet(): void { $r = new Router(); $r->get('/', fn() => 'yes'); self::assertSame(200, $r->dispatch(new Request('HEAD', '/'))->status); }
}
