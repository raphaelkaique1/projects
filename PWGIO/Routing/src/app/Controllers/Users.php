<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\View;

class Users
{
    public function all(): View
    {
        return View::render(view: 'users/index');
    }

    public function create(): View
    {
        return View::render(view: 'users/create');
    }

    public function store(): View
    {
        return View::render(
            view: 'users/index',
            params: $_POST
        );
    }
}