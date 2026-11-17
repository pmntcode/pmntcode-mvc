<?php

declare(strict_types=1);

namespace App\Controllers;

use PmntCode\Mvc\Contracts\ViewInterface;
use PmntCode\Mvc\Http\Response;

final class HomeController
{
    public function __construct(private readonly ViewInterface $views) {}
    public function index(): Response
    {
        return $this->views->render('home.index', ['title' => 'Início', 'name' => 'PmntCode MVC'], 'layouts.app');
    }
}
