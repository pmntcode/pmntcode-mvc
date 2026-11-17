<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Http;

final class Response
{
    public function __construct(public readonly string $body = '', public readonly int $status = 200, public readonly array $headers = ['Content-Type' => 'text/plain; charset=utf-8'])
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('Invalid HTTP status');
        }
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !preg_match('/^[A-Za-z0-9-]+$/D', $name) || !is_string($value) || strpbrk($value, "\r\n") !== false) {
                throw new \InvalidArgumentException('Invalid response header');
            }
        }
    }
    public static function json(mixed $data, int $status = 200): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR), $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }
    public function send(bool $head = false): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        if (!$head && $this->status !== 204 && $this->status !== 304) {
            echo $this->body;
        }
    }
}
