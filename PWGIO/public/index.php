<?php

declare(strict_types=1);

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

define('APP_PATH', $root . 'app' . DIRECTORY_SEPARATOR);
define('FILES_PATH', $root . 'transaction_files' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', $root . 'app' . DIRECTORY_SEPARATOR);

require_once APP_PATH . 'App.php';
require_once VIEWS_PATH . 'App.php';

header(
    'Content-Type: application/json',
    response_code: 200
);

echo $transactions
    = getFiles(dirPath: FILES_PATH)
    |> validate(...)
    |> normalize(...)
    |> displayBalance(...)
    |> json(...);

exit;