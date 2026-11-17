<?php

declare(strict_types=1);

namespace App\Controllers;

use PmntCode\Mvc\Http\Request;
use PmntCode\Mvc\Http\Response;

final class UserController
{
    public function show(Request $request, string $id): Response
    {
        return Response::json(['id' => $id, 'name' => 'Example']);
    }
}
