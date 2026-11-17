<?php
declare(strict_types=1);
namespace Tests;
use PHPUnit\Framework\TestCase;
use PmntCode\Mvc\Container\Container;
use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Http\Response;
use PmntCode\Mvc\Routing\Router;
final class ProductionRouterTest extends TestCase
{
    public function testNamedRouteAndConstraints(): void {
        $r = new Router(); $id = $r->get('/users/{id}', fn($req,$id) => $id);
        $r->where($id,'id','[0-9]+'); $r->name($id,'user');
        self::assertSame('/users/42',$r->url('user',['id'=>42]));
        self::assertSame(404,$r->dispatch(new Request('GET','/users/no'))->status);
        self::assertSame('42',$r->dispatch(new Request('GET','/users/42'))->body);
    }
    public function testInjectionAndMiddleware(): void {
        $container = new Container(); $container->instance(ExampleService::class,new ExampleService());
        $r = new Router($container); $r->middleware(fn($req,$next) => $next($req));
        $r->get('/di/{id}',[ExampleController::class,'show']);
        self::assertSame('service:7',$r->dispatch(new Request('GET','/di/7'))->body);
    }
    public function testErrorsAndMethodHandling(): void {
        $r = new Router(); $r->get('/hello',fn() => 'ok');
        self::assertSame(405,$r->dispatch(new Request('POST','/hello'))->status);
        self::assertSame('GET, HEAD',$r->dispatch(new Request('POST','/hello'))->headers['Allow']);
        self::assertSame(200,$r->dispatch(new Request('HEAD','/hello'))->status);
        self::assertSame(400,$r->dispatch(new Request('GET','/%ZZ'))->status);
        self::assertSame(400,$r->dispatch(new Request('GET','/%2e%2e/x'))->status);
        $r->get('/boom',fn() => throw new \RuntimeException('secret'));
        self::assertSame('500 Internal Server Error',$r->dispatch(new Request('GET','/boom'))->body);
    }
    public function testResponseHeaderInjectionRejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        new Response('ok',200,['X-Test'=>"good\r\nInjected: yes"]);
    }
}
final class ExampleService { public function label(): string { return 'service'; } }
final class ExampleController {
    public function __construct(private readonly ExampleService $service) {}
    public function show(Request $request,string $id): string { return $this->service->label() . ':' . $id; }
}
