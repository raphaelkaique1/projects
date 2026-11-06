<?php

declare(strict_types=1);

namespace Views;

final class JSON
{
    public static function setJSON(object|array $data): string
    {
        return ($json = json_encode(
            value: (object) $data,
            flags: JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        ))
        ? $json
        : throw new \Exception(
            message: "Error while attempting to form JSON."
        );
    }
}
