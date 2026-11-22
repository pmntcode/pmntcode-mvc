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
    public function recursos(): Response
    {
        return $this->views->render('recursos.index', ['title' => 'Recursos'], 'layouts.app');
    }

    public function arquitetura(): Response
    {
        return $this->views->render('arquitetura.index', ['title' => 'Arquitetura'], 'layouts.app');
    }

    public function comecar(): Response
    {
        return $this->views->render('comecar.index', ['title' => 'Começar'], 'layouts.app');
    }
}
