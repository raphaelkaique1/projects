<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\View;

class Home
{
    public function index(): View
    {
        return View::render(view: 'index');
    }

    public function upload(): View
    {
        move_uploaded_file(
            from: $_FILES['file']['tmp_name'],
            to: STORAGE_PATH . $_FILES['file']['name']
        );

        return View::render(
            view: 'index',
            params: ['alerts' => 'Created']
        );
    }
}
