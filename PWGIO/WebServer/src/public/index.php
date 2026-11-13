<?php

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$id = new Ramsey\Uuid\UuidFactory();

$token = new App\Auth\CSRF()::generate();

echo App\Auth\CSRF::validate(token: $token)
    ? $id->uuid4()
    : App\Enums\Status::UNAUTHORIZED->value;
