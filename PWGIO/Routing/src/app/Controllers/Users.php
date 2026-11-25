<?php

declare(strict_types = 1);

namespace App\Controllers;

class Users
{
    public function all(): string
    {
        return <<<HTML
            
        HTML;
    }

    public function create(): string
    {
        return <<<HTML

        HTML;
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