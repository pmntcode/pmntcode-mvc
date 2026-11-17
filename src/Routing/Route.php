<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Routing;

final class Route
{
    public function __construct(public readonly array $methods, public readonly string $path, public readonly mixed $handler, public readonly array $middleware = [], public readonly array $constraints = [], public readonly ?string $name = null) {}
    public function where(string $parameter, string $regex): self
    {
        return new self($this->methods, $this->path, $this->handler, $this->middleware, [...$this->constraints, $parameter => $regex], $this->name);
    }
    public function middleware(callable $middleware): self
    {
        return new self($this->methods, $this->path, $this->handler, [...$this->middleware, $middleware], $this->constraints, $this->name);
    }
    public function named(string $name): self
    {
        return new self($this->methods, $this->path, $this->handler, $this->middleware, $this->constraints, $name);
    }
}
