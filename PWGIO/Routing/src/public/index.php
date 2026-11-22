<?php

declare(strict_types = 1);

use App\Enums\HttpStatusCode;

require_once dirname(path: __DIR__) . '/vendor/autoload.php';

$router = new App\Router();

$router
    ->get(route: '/', action: [App\Classes\Home::class, 'index'])
    ->get(route: '/users', action: [App\Classes\Users::class, 'all'])
    ->get(route: '/users/create', action: [App\Classes\Users::class, 'create'])
    ->post(route: '/users/create', action: [App\Classes\Users::class, 'store']);

try {
    $page = $router->resolve(
        requestUri: $_SERVER['REQUEST_URI'],
        requestMethod: $_SERVER['REQUEST_METHOD']
    ) and $code = (HttpStatusCode::OK)()['code'];
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
