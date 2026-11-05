<?php

declare(strict_types=1);

use App\Transactions,
    Views\JSON;

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

define('APP_PATH', $root . 'app' . DIRECTORY_SEPARATOR);
define('FILES_PATH', $root . 'transaction_files' . DIRECTORY_SEPARATOR);
define('VIEWS_PATH', $root . 'views' . DIRECTORY_SEPARATOR);

require_once APP_PATH . 'App.php';
require_once VIEWS_PATH . 'JSON.php';

$transactions = new Transactions(
        dirPath: FILES_PATH
    )
    ->normalize(documentLocale: 'en_US')
    ->format(formatLocale: 'pt_BR');

header(
    'Content-Type: application/json',
    response_code: 200
);

echo JSON::setJSON($transactions->totals());

exit;