<?php

declare(strict_types=1);

namespace PmntCode\Mvc\View;

use PmntCode\Mvc\Contracts\ViewInterface;
use PmntCode\Mvc\Http\Response;

final class View implements ViewInterface
{
    private readonly string $root;
    public function __construct(string $directory)
    {
        $root = realpath($directory);
        if ($root === false || !is_dir($root)) {
            throw new \InvalidArgumentException('View directory does not exist');
        }
        $this->root = $root;
    }
    public function render(string $template, array $data = [], ?string $layout = null): Response
    {
        $content = $this->evaluate($template, $data);
        if ($layout !== null) {
            if (array_key_exists('content', $data)) {
                throw new \InvalidArgumentException('Reserved layout variable: content');
            }
            $content = $this->evaluate($layout, [...$data, 'content' => $content]);
        }
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function partial(string $template, array $data = []): string
    {
        return $this->evaluate($template, $data);
    }
    public function escape(mixed $value): string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable && $value !== null) {
            throw new \InvalidArgumentException('Cannot escape non-scalar view value');
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
    private function resolve(string $template): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/D', $template)) {
            throw new \InvalidArgumentException('Invalid view name');
        }
        $candidate = $this->root . '/' . str_replace('.', '/', $template) . '.php';
        $real = realpath($candidate);
        if ($real === false || !is_file($real) || !str_starts_with($real, $this->root . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('View not found');
        }
        return $real;
    }
    private function evaluate(string $template, array $data): string
    {
        $file = $this->resolve($template);
        foreach ($data as $key => $_) {
            if (!is_string($key) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $key) || $key === 'this' || str_starts_with($key, '__')) {
                throw new \InvalidArgumentException('Invalid view data key');
            }
        }
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $__file, array $__data, self $__view): void {
                extract($__data, EXTR_SKIP);
                $view = $__view;
                require $__file;
            })($file, $data, $this);
            $result = ob_get_contents();
            if ($result === false) {
                throw new \RuntimeException('Unable to read view buffer');
            }
            return $result;
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }
}
