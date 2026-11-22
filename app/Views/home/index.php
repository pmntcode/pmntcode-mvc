<section class="hero"><div class="container hero-grid"><div class="hero-copy"><span class="eyebrow"><span class="status-dot"></span> PHP 8.2+ · Estrutura leve e extensível</span><h1>Seu próximo projeto PHP começa com uma <span>base sólida.</span></h1><p>Uma fundação MVC minimalista para construir aplicações com rotas organizadas, controllers, injeção de dependências e views em PHP nativo.</p><div class="hero-actions"><a class="btn btn-primary" href="/comecar">Começar agora <span>→</span></a><a class="btn btn-secondary" href="/arquitetura">Conhecer a arquitetura</a></div><div class="hero-meta"><span>✓ Sem dependências de frontend</span><span>✓ Código organizado</span><span>✓ Open source</span></div></div><div class="code-window" aria-label="Exemplo de roteamento PHP"><div class="window-top"><div class="window-dots"><i></i><i></i><i></i></div><span>routes/web.php</span><span class="window-lang">PHP</span></div><pre><code><span class="code-muted">&lt;?php</span>

<span class="code-purple">use</span> App\Controllers\HomeController;

<span class="code-muted">// Sua aplicação, suas rotas.</span>
<span class="code-blue">$router</span>-&gt;<span class="code-yellow">get</span>(<span class="code-green">'/'</span>, [
    HomeController::<span class="code-purple">class</span>,
    <span class="code-green">'index'</span>
]);

<span class="code-blue">$router</span>-&gt;<span class="code-yellow">get</span>(<span class="code-green">'/hello/{name}'</span>,
    <span class="code-purple">fn</span> ($request) =&gt; <span class="code-green">'Olá!'</span>
);</code></pre><div class="code-bottom"><span><span class="status-dot"></span> Pronto para desenvolver</span><span>01 / 01</span></div></div></div></section>
