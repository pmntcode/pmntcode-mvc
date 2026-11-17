# PmntCode MVC 1.0.0

Router-focused PHP 8.2+ foundation. **No Model/View/ORM implementation.** This is a release candidate architecture; deployment readiness depends on successful CI, security review and deployment tests.

## Installation (Linux Mint)

```bash
sudo apt install php-cli php-mbstring php-xml php-curl unzip composer
composer install
composer check
php -S 127.0.0.1:8000 -t public public/router.php
```

Visit `http://127.0.0.1:8000`, `/hello/Ana`, `/api/v1/users/42`.

## Routing

```php
$router = new \PmntCode\Mvc\Routing\Router();
$id = $router->get('/users/{id}', [UserController::class, 'show']);
$router->where($id, 'id', '[0-9]+');
$router->name($id, 'users.show');
echo $router->url('users.show', ['id' => 42]); // /users/42
$router->group('/api', function ($r) { $r->post('/items', fn($request) => ['ok' => true]); });
```

Controller constructors are resolved by the container, including class-typed dependencies. Bind interfaces explicitly with `$container->bind(InterfaceName::class, fn($c) => new Implementation());`. Middleware receives `(Request $request, callable $next): Response`, and may short-circuit. MiddlewareInterface is supported. Route methods return an integer ID for `where()`, `name()` and `routeMiddleware()`.

## HTTP behavior and security

- Encoded URL path decoded once; invalid percent escapes, malformed UTF-8, dot segments and backslashes rejected with 400.
- Request path max 4096 bytes; server should also enforce request body/header/time limits.
- Errors logged server-side and generic 500 returned unless debug is enabled; **never enable debug in production**.
- Response header names and values validated to mitigate header injection.
- GET routes support HEAD. Unknown paths return 404; unsupported methods on matched paths return 405 with Allow.
- The built-in PHP server is **development only**; production requires Nginx or Apache pointing at `public/`, TLS, restricted filesystem permissions, and appropriate proxy configuration.
- Router does not provide authentication, CSRF protection, rate limiting, CORS policy, session management or request validation. Applications must implement these independently.
- Treat route constraints as trusted developer configuration, not user input; avoid catastrophic-backtracking regexes.

## Quality gates

```bash
composer test
composer analyse
find src app public routes -name '*.php' -print0 | xargs -0 -n1 php -l
composer audit
```

## Compatibility

The public API from 0.1.0 (`get`, `post`, `put`, `patch`, `delete`, `options`, `any`, `where`, `middleware`, `routeMiddleware`, `group`, `dispatch`) is retained. URL handling is stricter; invalid request paths now yield HTTP 400.

## Production checklist

Run unit and HTTP integration tests against the target PHP version, review dependency advisories, test deployment under Nginx/Apache and TLS, ensure production error display is disabled, configure log retention and monitoring, and add application-specific authentication, CSRF, rate limiting and input validation where relevant.

MIT License.


## Views e layouts (1.1.0)

O mecanismo de templates usa PHP nativo em `app/Views`, sem dependências adicionais.

- `ViewInterface::render('home.index', ['name' => 'Ana'], 'layouts.app')` retorna `Response` HTML.
- `ViewInterface::partial('partials.header', ['name' => 'Ana'])` retorna uma string HTML.
- Dentro das views, `$view->escape($value)` escapa dados não confiáveis.
- No layout, `$content` é HTML produzido pela view; **não** passe HTML de usuários diretamente para ele.
- Nomes de templates são restritos e caminhos reais são verificados para impedir path traversal e symlinks externos.
- Templates PHP são código confiável da aplicação: nunca renderize arquivos enviados por usuários.
- A aplicação registra `ViewInterface` no container em `public/index.php`; `HomeController` demonstra injeção pelo construtor.
- O framework não implementa CSRF, autenticação, CSP específica da aplicação ou sandbox de templates; configure essas proteções conforme a aplicação.

Execute `composer install`, `composer test` e `composer analyse` antes de implantar.


## Configuração de views (1.1.1)

Os templates da aplicação ficam em `app/Views/`, incluindo `layouts/`, `partials/` e `home/`.
O motor de renderização permanece em `src/View/View.php`, separado dos templates.

O diretório padrão é definido em `config/views.php` e utilizado pelo front controller
(`public/index.php`) e pelos testes (`tests/ViewTest.php`):

```php
$settings = require dirname(__DIR__) . '/config/views.php';
$container->instance(ViewInterface::class, new View($settings['path']));
```

Para mudar o diretório, altere somente `config/views.php`; o diretório deve existir
e conter templates PHP confiáveis. Não coloque `app/Views` dentro de `public/`.

### Migração de 1.1.0

Mova os templates de `resources/views/` para `app/Views/` e atualize qualquer
instanciação personalizada de `new View(...)` para usar `config/views.php`.
As assinaturas públicas do motor de views e dos controllers não mudaram.

### Verificação

```bash
composer install
composer test
composer analyse
php -S 127.0.0.1:8000 -t public public/router.php
```
