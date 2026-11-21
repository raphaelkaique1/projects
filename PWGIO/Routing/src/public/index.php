<?php

declare(strict_types = 1);

require_once dirname(path: __DIR__) . '/vendor/autoload.php';

$router = new App\Router();

$router
    ->register(route: '/', action: function (): string {
        return 'home';
    })
    ->register(route: '/users', action: function (): string {
        return 'users';
    });

try {
    $page = $router->resolve(requestUri: $_SERVER['REQUEST_URI']) and
    $code = 200;
} catch (Throwable $error) {
    $page = $error->getMessage() and
    $code = $error->getCode();
}

header(
    header: 'Content-Type: text/html',
    response_code: $code
);

echo <<<HTML
    <p>
        {$page}
    </p>
HTML;
