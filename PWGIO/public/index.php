<?php

declare(strict_types=1);

use App\Transactions,
    Views\JSON;

$root = dirname(path: __DIR__) . DIRECTORY_SEPARATOR;

define(constant_name: 'APP_PATH', value: $root . 'app' . DIRECTORY_SEPARATOR);
define(constant_name: 'FILES_PATH', value: $root . 'transaction_files' . DIRECTORY_SEPARATOR);
define(constant_name: 'VIEWS_PATH', value: $root . 'views' . DIRECTORY_SEPARATOR);

require_once APP_PATH . 'App.php';
require_once VIEWS_PATH . 'JSON.php';

$transactions = new Transactions(
        dirPath: FILES_PATH
    )
    ->normalize(documentLocale: 'en_US')
    ->format(formatLocale: 'pt_BR');

header(
    header: 'Content-Type: application/json',
    response_code: 200
);

echo JSON::setJSON(data: $transactions->totals());

exit;
