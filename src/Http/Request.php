<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Http;

final class Request
{
    public function __construct(public readonly string $method, public readonly string $path, public readonly array $query = [], public readonly array $headers = [], public readonly string $body = '') {}
    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), $path, $_GET, $headers, file_get_contents('php://input') ?: '');
    }
}
