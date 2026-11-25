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

    public function store(): string
    {
        $data = json_encode(
            value: $_POST,
            flags: JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        );

        return <<<HTML
        <pre>
        {$data}
        </pre>
        HTML;
    }
}