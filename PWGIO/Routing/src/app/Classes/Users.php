<?php

declare(strict_types = 1);

namespace App\Classes;

class Users
{
    public function all(): string
    {
        return <<<HTML
            <h1>USERS</h1>
        HTML;
    }

    public function create(): string
    {
        return <<<HTML
            <form action="/users/create" method="POST">
                <label>Name</label>
                <input type="text" name="name"/>
                <button type="submit">submit</button>
            </form>
        HTML;
    }

    public function store(): string
    {
        $post = json_encode(
            value: $_POST,
            flags: JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        );

        return <<<HTML
        <pre>
        {$post}
        </pre>
        HTML;
    }
}