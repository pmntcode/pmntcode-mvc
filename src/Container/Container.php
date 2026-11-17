<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Container;

use PmntCode\Mvc\Contracts\ContainerInterface;

final class Container implements ContainerInterface
{
    /** @var array<string, callable(self):object> */
    private array $factories = [];
    /** @var array<string,object> */
    private array $instances = [];
    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }
    public function instance(string $id, object $object): void
    {
        $this->instances[$id] = $object;
    }
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }
        if (!class_exists($id)) {
            throw new \RuntimeException('Service is not registered');
        }
        $reflection = new \ReflectionClass($id);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException('Service is not instantiable');
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $id();
        }
        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new \RuntimeException('Unresolvable constructor dependency');
            }
        }
        return $reflection->newInstanceArgs($args);
    }
}
