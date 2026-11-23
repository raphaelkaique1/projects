<?php

declare(strict_types = 1);

namespace App\Classes;

class Home
{
    public function index(): string
    {
        return <<<HTML
            <h1>HOME</h1>
            <form action="/upload" method="POST" enctype="multipart/form-data">
                <label for="file">File</label>
                <input type="file" name="file"/>
                <button type="submit">submit</button>
            </form>
        HTML;
    }

    public function upload(): string
    {
        $file = json_encode(
            value: $_FILES,
            flags: JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        );

        return <<<HTML
        <pre>
        {$file}
        </pre>
        HTML;
    }
}
