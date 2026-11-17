<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Routing;

use PmntCode\Mvc\Contracts\RouterInterface;
use PmntCode\Mvc\Contracts\ContainerInterface;
use PmntCode\Mvc\Contracts\MiddlewareInterface;
use PmntCode\Mvc\Container\Container;
use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Http\Response;

final class Router implements RouterInterface
{
    /** @var list<Route> */
    private array $routes = [];
    private array $globalMiddleware = [];
    private string $prefix = '';
    private array $groupMiddleware = [];
    public function __construct(private readonly ContainerInterface $container = new Container(), private readonly bool $debug = false) {}
    public function add(array|string $methods, string $path, callable|array|string $handler): int
    {
        $methods = array_values(array_unique(array_map('strtoupper', (array) $methods)));
        if (!$methods || array_diff($methods, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'])) {
            throw new \InvalidArgumentException('Unsupported HTTP method');
        }
        $route = new Route($methods, $this->normalize($this->prefix . '/' . ltrim($path, '/')), $handler, $this->groupMiddleware);
        $this->routes[] = $route;
        return array_key_last($this->routes);
    }
    public function get(string $path, callable|array|string $handler): int
    {
        return $this->add('GET', $path, $handler);
    }
    public function post(string $path, callable|array|string $handler): int
    {
        return $this->add('POST', $path, $handler);
    }
    public function put(string $path, callable|array|string $handler): int
    {
        return $this->add('PUT', $path, $handler);
    }
    public function patch(string $path, callable|array|string $handler): int
    {
        return $this->add('PATCH', $path, $handler);
    }
    public function delete(string $path, callable|array|string $handler): int
    {
        return $this->add('DELETE', $path, $handler);
    }
    public function options(string $path, callable|array|string $handler): int
    {
        return $this->add('OPTIONS', $path, $handler);
    }
    public function head(string $path, callable|array|string $handler): int
    {
        return $this->add('HEAD', $path, $handler);
    }
    public function any(string $path, callable|array|string $handler): int
    {
        return $this->add(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $path, $handler);
    }
    public function where(int $routeId, string $parameter, string $regex): void
    {
        $route = $this->routeAt($routeId);
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $parameter) || !preg_match('/\{' . preg_quote($parameter, '/') . '\??\}/', $route->path)) {
            throw new \InvalidArgumentException('Unknown parameter');
        }
        if ($regex === '' || strlen($regex) > 256 || str_contains($regex, '~') || str_contains($regex, '(?') || preg_match('~^' . $regex . '$~D', '') === false) {
            throw new \InvalidArgumentException('Invalid route constraint');
        }
        $this->routes[$routeId] = $route->where($parameter, $regex);
    }
    public function name(int $routeId, string $name): void
    {
        if ($name === '' || isset($this->namedRoutes()[$name])) {
            throw new \InvalidArgumentException('Duplicate or empty route name');
        }
        $this->routes[$routeId] = $this->routeAt($routeId)->named($name);
    }
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->namedRoutes()[$name] ?? throw new \InvalidArgumentException('Unknown route name');
        $path = preg_replace_callback('~(/?)\{([A-Za-z_][A-Za-z0-9_]*)(\?)?\}~', function (array $m) use ($parameters, $route): string {
            $key = $m[2];
            if (!array_key_exists($key, $parameters)) {
                if (($m[3] ?? '') === '?') {
                    return '';
                }
                throw new \InvalidArgumentException('Missing route parameter');
            }
            if (!is_scalar($parameters[$key])) {
                throw new \InvalidArgumentException('Invalid route parameter');
            }
            $value = (string) $parameters[$key];
            $constraint = $route->constraints[$key] ?? '[^/]+';
            if ($value === '' || preg_match('~^' . $constraint . '$~D', $value) !== 1 || str_contains($value, '/')) {
                throw new \InvalidArgumentException('Route parameter violates constraint');
            }
            return $m[1] . rawurlencode($value);
        }, $route->path);
        return $path === '' ? '/' : (string) $path;
    }
    public function routeMiddleware(int $routeId, callable|MiddlewareInterface $middleware): void
    {
        $this->routes[$routeId] = $this->routeAt($routeId)->middleware($this->middlewareCallable($middleware));
    }
    public function middleware(callable|MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $this->middlewareCallable($middleware);
    }
    private function middlewareCallable(callable|MiddlewareInterface $middleware): callable
    {
        return $middleware instanceof MiddlewareInterface ? $middleware->process(...) : $middleware;
    }
    public function group(string $prefix, callable $register, array $middleware = []): void
    {
        $oldPrefix = $this->prefix;
        $oldMiddleware = $this->groupMiddleware;
        $this->prefix = $this->normalize($oldPrefix . '/' . trim($prefix, '/'));
        $this->groupMiddleware = [...$oldMiddleware, ...array_map($this->middlewareCallable(...), $middleware)];
        try {
            $register($this);
        } finally {
            $this->prefix = $oldPrefix;
            $this->groupMiddleware = $oldMiddleware;
        }
    }
    public function dispatch(Request $request): Response
    {
        try {
            return $this->dispatchInternal($request);
        } catch (\Throwable $exception) {
            error_log('[PmntCode MVC] ' . $exception::class . ': ' . $exception->getMessage());
            return new Response($this->debug ? $exception->getMessage() : '500 Internal Server Error', 500);
        }
    }
    private function dispatchInternal(Request $request): Response
    {
        if (!preg_match('/^[A-Z]+$/D', $request->method)) {
            return new Response('400 Bad Request', 400);
        }
        $path = $request->path;
        if ($path === '' || $path[0] !== '/' || strlen($path) > 4096 || str_contains($path, "\0") || preg_match('/%(?![0-9a-fA-F]{2})/', $path)) {
            return new Response('400 Bad Request', 400);
        }
        $path = rawurldecode($path);
        if (!preg_match('//u', $path) || preg_match('~(?:^|/)(?:\.|\.\.)(?:/|$)~', $path) || str_contains($path, chr(92)) || str_contains($path, "\0")) {
            return new Response('400 Bad Request', 400);
        }
        $path = $this->normalize($path);
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($this->compile($route), $path, $matches) !== 1) {
                continue;
            }
            $allowed = [...$allowed, ...$route->methods];
            if (!in_array($request->method, $route->methods, true) && !($request->method === 'HEAD' && in_array('GET', $route->methods, true))) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = function (Request $req) use ($route, $params): Response {
                $target = $route->handler;
                if (is_string($target) && str_contains($target, '@')) {
                    $target = explode('@', $target, 2);
                }
                if (is_array($target) && is_string($target[0])) {
                    $target = [$this->container->get($target[0]), $target[1]];
                }
                if (!is_callable($target)) {
                    throw new \LogicException('Route handler is not callable');
                }
                $result = $target($req, ...array_values($params));
                return $result instanceof Response ? $result : (is_array($result) ? Response::json($result) : new Response((string) $result));
            };
            foreach (array_reverse([...$this->globalMiddleware, ...$route->middleware]) as $mw) {
                $next = $handler;
                $handler = static fn(Request $req): Response => $mw($req, $next);
            }
            return $handler($request);
        }
        if ($allowed) {
            $allowed = array_values(array_unique([...$allowed, ...(in_array('GET', $allowed, true) ? ['HEAD'] : [])]));
            return new Response('405 Method Not Allowed', 405, ['Content-Type' => 'text/plain; charset=utf-8', 'Allow' => implode(', ', $allowed)]);
        }
        return new Response('404 Not Found', 404);
    }
    private function routeAt(int $id): Route
    {
        return $this->routes[$id] ?? throw new \InvalidArgumentException('Unknown route id');
    }
    private function namedRoutes(): array
    {
        $named = [];
        foreach ($this->routes as $route) {
            if ($route->name !== null) {
                $named[$route->name] = $route;
            }
        }
        return $named;
    }
    private function normalize(string $path): string
    {
        return '/' . trim(preg_replace('~/+~', '/', $path) ?? '/', '/');
    }
    private function compile(Route $route): string
    {
        $parts = preg_split('~(\{[A-Za-z_][A-Za-z0-9_]*\??\})~', $route->path, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('~^\{([A-Za-z_][A-Za-z0-9_]*)(\?)?\}$~', $part, $m)) {
                $name = $m[1];
                $rule = $route->constraints[$name] ?? '[^/]+';
                $piece = '(?P<' . $name . '>' . $rule . ')';
                if (($m[2] ?? '') === '?' && str_ends_with($regex, '/')) {
                    $regex = substr($regex, 0, -1) . '(?:/' . $piece . ')?';
                } else {
                    $regex .= $piece;
                }
            } else {
                $regex .= preg_quote($part, '~');
            }
        }
        return '~^' . $regex . '$~Du';
    }
}
