<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function response(
        string $data,
        string $contentType,
        int $responseCode
    ): void {

        header(
            header: "Content-Type: {$contentType}",
            response_code: $responseCode
        );

        echo $data;
    }
}