<?php

declare(strict_types = 1);

namespace App\Classes;

class Home
{
    public function index(): string
    {
        return <<<HTML
            <h1>HOME</h1>
        HTML;
    }
}
