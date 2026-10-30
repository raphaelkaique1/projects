<?php

/**
 * @depends
 * php8.5-intl
 * php8.5-bcmath
 */

declare(strict_types=1);

function getFiles(string $dirPath): array
{
    $files = [];
    $dir = scandir(directory: $dirPath) or throw new \Exception(
        message: "Failed to open directory {$dirPath}"
    );

    foreach ($dir as $file) {
        !is_dir(filename: $file) && $files[] = $dirPath . $file;
    }

    return $files;
}

function validate(array $fileName): array
{
    $transactions = [];

    foreach ($fileName as $file) {
        ($fileErrorStatus = match (true) {
            !is_file(filename: $file) => 'is not a regular file',
            !is_readable(filename: $file) => 'is not readable',
            default => null
        }) && throw new \Exception(
            message: "File {$file} {$fileErrorStatus}."
        );

        ($actualFile = fopen($file, 'r')) || throw new \Exception(
            message: "Could not open file {$file}."
        );

        try {
            while (($transaction = fgetcsv($actualFile)) !== false) {
                $transactions[] = $transaction;
            }
        } catch(\Throwable $error) {
            throw new \Exception(
                message: "Error while reading {$file}: {$error}"
            );
        } finally {
            fclose(stream: $actualFile);
            clearstatcache(clear_realpath_cache: true, filename: $file);
        }
    }

    return $transactions;
}

function normalize(array $transactions): array
{
    $originalHeaders = array_shift(array: $transactions);

    $headers = $originalHeaders
        |> (
            fn(array $array): array => preg_replace(
                pattern: '/[^\p{L}]/u',
                replacement: '',
                subject: $array
            )
        )
        |> (
            fn(array $array): array => array_map(
                callback: fn(string $path) => mb_convert_case(
                    string: $path,
                    mode: MB_CASE_TITLE
                ),
                array: $array
            )
        );

    return array_map(
        callback: function (array $transaction) use(&$headers): array {
            $transaction[0] = (string) (\DateTimeImmutable::createFromFormat(
                format: 'm/d/Y',
                datetime: $transaction[0]
            ))->format(format: 'd/m/Y');

            $transaction[1] = (int) $transaction[1] ?? 0;

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
            |> (
                fn(float $value): string|false => (new \NumberFormatter(
                    locale: 'pt_BR',
                    style: \NumberFormatter::CURRENCY
                ))->formatCurrency(
                    amount: $value,
                    currency: 'BRL'
                )
            );

            return array_combine(keys: $headers, values: $transaction);
        },
        array: array_filter(
            array: $transactions,
            callback: fn(array $transaction): bool => $transaction !== $originalHeaders
        )
    );
}

function displayBalance(array $transactions): array
{
    $deposits = [];
    $withdrawls = [];

    array_walk(
        array: $transactions,
        callback: function (
                array $transaction
            ) use (&$deposits, &$withdrawls): array {

                $currency = 'BRL';

                $save = static function (
                    array &$list,
                    float &$amount,
                    string &$description,
                    string &$original,
                    string $operation
                ): string {
                    $list['Formatted'][] = $amount;
                    $list['Original'][$description] = $original;

                    return $operation;
                };

                $amount = (new \NumberFormatter(
                    locale: 'pt_BR',
                    style: \NumberFormatter::DECIMAL
                ))->parseCurrency(
                    string: $transaction['Amount'],
                    currency: $currency
                );

                $description = preg_replace(
                    pattern: '/\s/',
                    replacement: '_',
                    subject: $transaction['Description']
                );

                $transaction['Operation'] =
                    str_contains(
                        haystack: $transaction['Amount'],
                        needle: '-'
                    )
                        ? $save(
                            list: $withdrawls,
                            amount: $amount,
                            description: $description,
                            original: $transaction['Amount'],
                            operation: 'Withdrawal'
                        )
                        : $save(
                            list: $deposits,
                            amount: $amount,
                            description: $description,
                            original: $transaction['Amount'],
                            operation: 'Deposit'
                        );

                return $transaction;
            }
    );

    [$deposits['Total_Qnt'], $deposits['Total_Value']] = [
        count(value: $deposits['Original']),
        (new \NumberFormatter(
            locale: 'pt_BR',
            style: \NumberFormatter::CURRENCY
        ))->formatCurrency(
            amount: $depositsValue = array_sum(array: $deposits['Formatted']),
            currency: 'BRL'
        )
    ];

    [$withdrawls['Total_Qnt'], $withdrawls['Total_Value']] = [
        count(value: $withdrawls['Original']),
        (new \NumberFormatter(
            locale: 'pt_BR',
            style: \NumberFormatter::CURRENCY
        ))->formatCurrency(
            amount: $withdrawlsValue = array_sum(array: $withdrawls['Formatted']),
            currency: 'BRL'
        )
    ];

    return [
        'Transactions' => [...$transactions],
        'Deposits' => [
            'Total_Qnt' => $deposits['Total_Qnt'],
            'Total_Value' => $deposits['Total_Value'],
            ...$deposits['Original']
        ],
        'Withdrawls' => [
            'Total_Qnt' => $withdrawls['Total_Qnt'],
            'Total_Value' => $withdrawls['Total_Value'],
            ...$withdrawls['Original']
        ],
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

function json(array $transactions): string
{
    return ($json = json_encode(
        value: (object) $transactions,
        flags: JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
    ))
    ? $json
    : throw new \Exception(
        message: "Error while attempting to form JSON."
    );
}