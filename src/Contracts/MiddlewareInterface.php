<?php

declare(strict_types=1);

namespace PmntCode\Mvc\Contracts;

use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}
