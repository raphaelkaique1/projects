<?php

declare(strict_types = 1);

namespace App\Auth;

final class CSRF
{
    public static function generate(): string
    {
        return $_SESSION['csrf_token']
            ??= bin2hex(string: random_bytes(length: 32));
    }

    public static function validate(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals(
                known_string: $_SESSION['csrf_token'],
                user_string: $token
            );
    }
}