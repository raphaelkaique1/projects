<?php

declare(strict_types=1);

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

define('APP_PATH', $root . 'app' . DIRECTORY_SEPARATOR);
define('FILES_PATH', $root . 'transaction_files' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', $root . 'app' . DIRECTORY_SEPARATOR);

require_once APP_PATH . 'App.php';
require_once VIEWS_PATH . 'App.php';

$transactions = getTransactionFiles(dirPath: FILES_PATH)
    |> getTransactions(...)
    |> formatTransactions(...)
    |> displayBalance(...);

echo '<pre>';
print_r($transactions);
echo '</pre>';