# PmntCode MVC

Framework leve para aplicações PHP **8.2+**, com roteamento HTTP, controllers, injeção de dependências, views e layouts em PHP nativo. O projeto inclui um site demonstrativo responsivo e exemplos de rotas de API.

> **Estado do projeto:** base em desenvolvimento. Antes de usar em produção, execute testes, revise a segurança e valide a implantação. O projeto não fornece ORM, autenticação, proteção CSRF, gerenciamento de sessões, limitação de requisições ou validação de entradas pronta para uso.

**Repositório:** https://github.com/pmntcode/pmntcode-mvc

## Requisitos e instalação

- PHP 8.2 ou superior
- Composer
- Extensões PHP necessárias às ferramentas de desenvolvimento, como `mbstring` e `xml`

No Linux Mint (com uma versão compatível do PHP disponível):

```bash
sudo apt update
sudo apt install php-cli php-mbstring php-xml php-curl unzip composer
composer install
```

Para verificar os testes e a análise estática:

```bash
composer check
```

## Executar em desenvolvimento

O projeto contém `public/router.php` para o servidor embutido do PHP:

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

Abra `http://127.0.0.1:8000/`. O servidor embutido destina-se somente a desenvolvimento.

## Apache e .htaccess

No Apache, configure o **DocumentRoot** para o diretório `public/`, habilite `mod_rewrite` e permita regras de reescrita (`AllowOverride FileInfo` ou `All`). Crie `public/.htaccess` com:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

O `.htaccess` encaminha URLs que não correspondem a arquivos ou diretórios reais para `public/index.php`, o **front controller**. Esse arquivo inicializa o container, carrega `routes/web.php`, cria a requisição e chama o Router MVC. O arquivo `public/router.php` não é necessário no Apache. **A configuração `.htaccess` acima é uma opção documentada; ela não foi incluída automaticamente no projeto.**

## Rotas existentes

Definidas em `routes/web.php`:

| Método | Caminho              | Finalidade                                       |
| ------ | -------------------- | ------------------------------------------------ |
| GET    | `/`                  | Página inicial                                   |
| GET    | `/recursos`          | Recursos do framework                            |
| GET    | `/arquitetura`       | Arquitetura MVC                                  |
| GET    | `/comecar`           | Guia para começar                                |
| GET    | `/hello/{name}`      | Exemplo JSON de saudação                         |
| GET    | `/api/v1/users/{id}` | Exemplo de consulta de usuário (ID numérico)     |
| POST   | `/api/v1/echo`       | Exemplo de resposta JSON com corpo da requisição |

As quatro páginas do site são views separadas, embora seus métodos estejam atualmente no mesmo `HomeController`.

Exemplo de declaração de rota:

```php
$router->get('/recursos', [HomeController::class, 'recursos']);

$idRoute = $router->get('/api/v1/users/{id}', [UserController::class, 'show']);
$router->where($idRoute, 'id', '[0-9]+');
$router->name($idRoute, 'users.show');
```

O Router também disponibiliza `post`, `put`, `patch`, `delete`, `options`, `any`, `group`, `middleware`, `routeMiddleware`, `where`, `name`, `url` e `dispatch`. Rotas podem usar middlewares e restrições de parâmetros. Uma rota GET também aceita HEAD; caminhos inexistentes retornam 404 e métodos não permitidos retornam 405 com `Allow`.

## Arquitetura e organização

```text
app/
  Controllers/          # HomeController e UserController
  Views/
    home/               # Página inicial
    recursos/           # Página de recursos
    arquitetura/        # Página de arquitetura
    comecar/            # Guia inicial
    layouts/            # Layout HTML compartilhado
    partials/           # Cabeçalho e rodapé
config/views.php        # Caminho dos templates
public/
  index.php             # Front controller
  router.php            # Suporte ao servidor PHP embutido
  assets/css/app.css    # Estilos responsivos
routes/web.php          # Definições de rotas
src/                    # Núcleo do framework
  Container/            # Resolução de dependências
  Routing/              # Router MVC
  View/                 # Renderização de templates
tests/                  # Testes automatizados
```

O fluxo de uma requisição é: **servidor web → `public/index.php` → Router → Controller → View/Response**.

## Injeção de dependências

O `Container` resolve dependências de classes nos construtores dos controllers. O `HomeController`, por exemplo, recebe `ViewInterface`:

```php
public function __construct(private readonly ViewInterface $views) {}
```

Em `public/index.php`, a implementação é registrada explicitamente:

```php
$container = new Container();
$container->instance(
    ViewInterface::class,
    new View((require dirname(__DIR__) . '/config/views.php')['path'])
);
$router = new Router($container);
```

Interfaces podem ser associadas a implementações com `bind(...)`. Dependências de classes concretas podem ser resolvidas automaticamente quando seus construtores são compatíveis.

## Views, layouts e segurança de saída

Os templates ficam em `app/Views/` e são renderizados com PHP nativo, sem engine externa. Exemplo:

```php
return $this->views->render(
    'home.index',
    ['title' => 'Início', 'name' => 'PmntCode MVC'],
    'layouts.app'
);
```

- `render(...)` produz uma resposta HTML.
- `partial('partials.header', [...])` produz um trecho HTML.
- Use `$view->escape($value)` ao exibir dados não confiáveis.
- O layout recebe `$content` com HTML renderizado; não injete HTML fornecido por usuários sem tratamento.
- O diretório de templates é definido em `config/views.php`. Mantenha-o fora de `public/`.
- Templates PHP são código confiável; não renderize arquivos enviados por usuários.

O cabeçalho compartilha navegação entre as páginas e o botão **Explorar projeto** abre o [repositório no GitHub](https://github.com/pmntcode/pmntcode-mvc) em outra aba.

## Testes e qualidade

```bash
composer test
composer analyse
composer check
composer audit
find src app public routes -name '*.php' -print0 | xargs -0 -n1 php -l
```

A execução bem-sucedida desses comandos depende das extensões PHP e dependências disponíveis no ambiente. Não presuma que a aplicação está pronta para produção apenas porque passou nos testes locais.

## Cuidados de segurança e produção

- Utilize Apache ou Nginx com `public/` como raiz do site e HTTPS configurado.
- Desative a exibição de erros e o modo debug em produção; configure logs e monitoramento.
- Implemente autenticação, autorização, CSRF, validação de entrada, sessões e rate limiting conforme a aplicação.
- A camada HTTP rejeita caminhos inválidos e valida cabeçalhos de resposta; configure também limites de corpo, cabeçalhos e tempo no servidor.
- Execute testes HTTP e de integração no ambiente-alvo, revise dependências e permissões de arquivos.
- Trate expressões regulares de restrição de rotas como configuração confiável do desenvolvedor.

## Licença

Distribuído sob a licença MIT. Consulte `LICENSE`.
