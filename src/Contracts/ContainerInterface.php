<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Contracts;

interface ContainerInterface
{
    public function get(string $id): object;
}
