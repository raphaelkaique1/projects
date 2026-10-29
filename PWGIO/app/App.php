<?php

/**
 * @depends
 * php8.5-intl
 * php8.5-bcmath
 */

declare(strict_types=1);

function getTransactionFiles(string $dirPath): array
{
    $files = [];

    foreach (scandir(directory: $dirPath) as $file) {
        is_dir(filename: $file) ?: $files[] = $dirPath . $file;
    }

    return $files;
}

function getTransactions(array $fileName): array
{
    $transactions = [];

    foreach ($fileName as $file) {
        file_exists(filename: $file)
        ?: throw new Exception(message: "File {$file} does not exist.");

        $actualFile = fopen(filename: $file, mode: 'r');

        while(($transaction = fgetcsv(stream: $actualFile)) !== false) {
            $transactions[] = $transaction;
        }

        fclose(stream: $actualFile);
        clearstatcache(clear_realpath_cache: true, filename: $file);
    }

    return $transactions;
}

function formatTransactions(array $transactions): array
{
    $keys = array_shift(array: $transactions)
    |> (fn(array $array): array => preg_replace(
            pattern: '/[^\p{L}]/u',
            replacement: '',
            subject: $array
        )
    )
    |> (fn(array $array): array => array_map(
            callback: fn(string $path) => mb_convert_case(
                string: $path,
                mode: MB_CASE_TITLE
            ),
            array: $array
        )
    );

    return array_map(
        callback: function (array $transaction) use($keys): array {

            $transaction[0] = (string) (\DateTimeImmutable::createFromFormat(
                format: 'm/d/Y',
                datetime: $transaction[0]
            ))->format(format: 'd/m/Y');

            $transaction[1] = (int) $transaction[1] ?: 0;

            $transaction[2] = (string) preg_replace(
                pattern: '/[^\p{L}\s0-9]/u',
                replacement: '',
                subject: $transaction[2]
            ) |> trim(...);

            $transaction[3] = (float) preg_replace(
                pattern: ['/[^\-\.0-9]/'],
                replacement: '',
                subject: $transaction[3]
            )
            |> (fn(float $value): string|false => (new \NumberFormatter(
                    locale: 'pt_BR',
                    style: \NumberFormatter::CURRENCY
                ))->formatCurrency(
                    amount: $value,
                    currency: 'BRL'
                )
            );

            return array_combine(keys: $keys, values: $transaction);
        },
        array: $transactions
    );
}

function displayBalance(array $transactions): array
{
    $deposits = [];
    $withdrawls = [];

    $transactions = array_map(
        callback: function (array $transaction) use (&$deposits, &$withdrawls): array {

            $currency = 'BRL';

            $amount = (new \NumberFormatter(
                locale: 'pt_BR',
                style: \NumberFormatter::DECIMAL
            ))->parseCurrency(
                string: $transaction['Amount'],
                currency: $currency
            );

            $transaction['Operation'] =
                str_contains(
                    haystack: $transaction['Amount'],
                    needle: '-'
                )
                ? ($withdrawls[] = (float) $amount) && 'Withdrawal'
                : ($deposits[] = (float) $amount) && 'Deposit';
            return $transaction;
        },
        array: $transactions
    );

    [$deposits['Total_Qnt'], $deposits['Total_Value']] = [
        count(value: $deposits),
        (new \NumberFormatter(
            locale: 'pt_BR',
            style: \NumberFormatter::CURRENCY
        ))->formatCurrency(
            amount: $depositsValue = array_sum(array: $deposits),
            currency: 'BRL'
        )
    ];

    [$withdrawls['Total_Qnt'], $withdrawls['Total_Value']] = [
        count(value: $withdrawls),
        (new \NumberFormatter(
            locale: 'pt_BR',
            style: \NumberFormatter::CURRENCY
        ))->formatCurrency(
            amount: $withdrawlsValue = array_sum(array: $withdrawls),
            currency: 'BRL'
        )
    ];

    return [
        ...$transactions,
        'Deposits' => $deposits,
        'Withdrawls' => $withdrawls,
        'Balance' => 
        (new \NumberFormatter(
            locale: 'pt_BR',
            style: \NumberFormatter::CURRENCY
        ))->formatCurrency(
            amount: (float) bcsub(
                num1: (string) $depositsValue,
                num2: (string) -$withdrawlsValue,
                scale: 2
            ),
            currency: 'BRL'
        )
    ];
}