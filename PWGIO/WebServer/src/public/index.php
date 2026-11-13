<?php

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$id = new Ramsey\Uuid\UuidFactory();

$token = new App\CSRF()::generate();

echo $id->uuid4() . '<br/>' . $token;