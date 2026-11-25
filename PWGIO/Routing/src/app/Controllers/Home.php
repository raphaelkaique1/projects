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

    public function upload(): string
    {
        move_uploaded_file(
            from: $uploadedFile = $_FILES['file']['tmp_name'],
            to: $savedFile = STORAGE_PATH . $_FILES['file']['name']
        );

        $file = json_encode(
            value: (object) [
                [
                    'from' => $uploadedFile,
                    ...$_FILES
                ],
                [
                    'to' => $savedFile,
                    'dir' => pathinfo(path: $savedFile)
                ]
            ],
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
