<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Contracts;

use PmntCode\Mvc\Http\Response;

interface ViewInterface
{
    public function render(string $template, array $data = [], ?string $layout = null): Response;
    public function partial(string $template, array $data = []): string;
    public function escape(mixed $value): string;
}
