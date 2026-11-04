<?php

/**
 * @depends
 * php8.5-intl
 * php8.5-bcmath
 */

declare(strict_types=1);

namespace App;

use Locale,
    NumberFormatter,
    IntlDateFormatter,
    DateTimeImmutable,
    DateTimeZone,
    IntlException;

final class Transactions
{
    public private(set) array $files = [];
    public private(set) array $transactions = [];

    public function __construct(
        public private(set) string $dirPath
    ) {
        $dir = scandir(directory: $dirPath) or throw new \Exception(
            message: "Failed to open directory '{$dirPath}'."
        );

        foreach ($dir as $file) {
            !is_dir(filename: $file)
                && $this->files[] = $this->dirPath . $file;
        }

        foreach ($this->files as $file) {
            ($fileErrorStatus = match (true) {
                !is_file(filename: $file) => 'is not a regular file',
                !is_readable(filename: $file) => 'is not readable',
                default => null
            }) && throw new \Exception(
                message: "File '{$file}' {$fileErrorStatus}."
            );

            ($actualLine = fopen($file, 'r')) || throw new \Exception(
                message: "Could not open file '{$file}'."
            );

            try {
                while (($transaction = fgetcsv($actualLine)) !== false) {
                    $this->transactions[] = $transaction;
                }
            } catch(\Throwable $error) {
                throw new \Exception(
                    message: "Error while reading '{$file}'.",
                    previous: $error
                );
            } finally {
                fclose(stream: $actualLine);
                clearstatcache(clear_realpath_cache: true, filename: $file);
            }
        }
    }

    public function normalize(string $documentLocale): self
    {
        $documentLocale = Locale::canonicalize(locale: $documentLocale)
            ?? throw new IntlException(
                message: "Parameter (locale: '{$documentLocale}') is not a valide locale identifier."
            );

        $headers
            = array_shift(array: $this->transactions)
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

        $headers[] = 'Currency';

        $this->transactions = array_map(
            callback: function (array $transaction) use(&$documentLocale, &$headers): array {
                $timestamp = (int) new IntlDateFormatter(
                        locale: $documentLocale,
                        dateType: IntlDateFormatter::SHORT,
                        timeType: IntlDateFormatter::NONE,
                        timezone: 'UTC',
                        calendar: IntlDateFormatter::GREGORIAN
                    )
                    ->parse(
                        string: $transaction[0]
                    );

                $transaction[0] = (string) new IntlDateFormatter(
                        locale: null,
                        pattern: 'yyyy-MM-dd'
                    )
                    ->format(
                        datetime: $timestamp
                    );

                $transaction[1] = (int) ($transaction[1] ?? 0);

                $transaction[2] = (string) preg_replace(
                        pattern: '/[^\p{L}\s0-9]/u',
                        replacement: '',
                        subject: $transaction[2]
                    ) |> trim(...);

                $transaction[3] = (float) preg_replace(
                        pattern: ['/[^\-\.0-9]/'],
                        replacement: '',
                        subject: $transaction[3]
                    );

                $transaction[4] = (string) new NumberFormatter(
                        locale: $documentLocale,
                        style: NumberFormatter::CURRENCY
                    )
                    ->getTextAttribute(
                        attribute: NumberFormatter::CURRENCY_CODE
                    );

                return array_combine(keys: $headers, values: $transaction);
            },
            array: $this->transactions
        );

        return $this;
    }

    public function format(string $formatLocale): array
    {
        $formatLocale = Locale::canonicalize(locale: $formatLocale)
            ?? throw new IntlException(
                message: "Parameter (locale: '{$formatLocale}') is not a valide locale identifier."
            );

        $transactions = array_map(
            callback: function (
                    array $transaction
                ) use (&$formatLocale): array {
                    $transaction['Description'] = preg_replace(
                            pattern: '/\s/',
                            replacement: '',
                            subject: $transaction['Description']
                        );

                    $transaction['Date'] = new IntlDateFormatter(
                            locale: $formatLocale,
                            dateType: IntlDateFormatter::SHORT,
                            timeType: IntlDateFormatter::NONE,
                            timezone: 'UTC',
                            calendar: IntlDateFormatter::GREGORIAN
                        )
                        ->format(
                            datetime: new DateTimeImmutable(
                                datetime: $transaction['Date'],
                                timezone: new DateTimeZone(timezone: 'UTC')
                            )
                        );

                    $currrencyAmount = new NumberFormatter(
                            locale: $formatLocale,
                            style: NumberFormatter::DECIMAL
                        )
                        ->format(
                            num: $transaction['Amount']
                        );
                    
                    $transaction['Amount'] = $currrencyAmount;

                    $transaction['Operation'] =
                        $transaction['Amount'] < 0
                            ? 'Withdrawal'
                            : 'Deposit';

                    return $transaction;
                },
            array: $this->transactions
        );

        return [
            'Transactions' => [...$transactions]
        ];
    }

    public function totals(string $formatLocale): array
    {
        $formatLocale = Locale::canonicalize(locale: $formatLocale)
            ?? throw new IntlException(
                message: "Parameter (locale: '{$formatLocale}') is not a valide locale identifier."
            );

        $currencyFormatter = new NumberFormatter(
            locale: $formatLocale,
            style: NumberFormatter::DECIMAL
        );

        $deposits = [];
        $withdrawls = [];

        $transactions = array_map(
            callback: function (
                    array $transaction
                ) use (&$formatLocale, &$deposits, &$withdrawls): array {

                    $save = static function (
                        array &$list,
                        string &$description,
                        float &$originalAmount,
                        string &$formatedAmount,
                        string $operation
                    ): string {
                        $list['Original'][] = $originalAmount;
                        $list['Formatted'][$description] = $formatedAmount;

                        return $operation;
                    };

                    $transaction['Date'] = new IntlDateFormatter(
                            locale: $formatLocale,
                            dateType: IntlDateFormatter::SHORT,
                            timeType: IntlDateFormatter::NONE,
                            timezone: 'UTC',
                            calendar: IntlDateFormatter::GREGORIAN
                        )
                        ->format(
                            datetime: new DateTimeImmutable(
                                datetime: $transaction['Date'],
                                timezone: new DateTimeZone(timezone: 'UTC')
                            )
                        );

                    $currrencyAmount = new NumberFormatter(
                            locale: $formatLocale,
                            style: NumberFormatter::DECIMAL
                        )
                        ->format(
                            num: $transaction['Amount']
                        );

                    $transaction['Description'] = preg_replace(
                        pattern: '/\s/',
                        replacement: '',
                        subject: $transaction['Description']
                    );

                    $transaction['Operation'] =
                        $transaction['Amount'] < 0
                            ? $save(
                                list: $withdrawls,
                                description: $transaction['Description'],
                                formatedAmount: $currrencyAmount,
                                originalAmount: $transaction['Amount'],
                                operation: 'Withdrawal'
                            )
                            : $save(
                                list: $deposits,
                                description: $transaction['Description'],
                                formatedAmount: $currrencyAmount,
                                originalAmount: $transaction['Amount'],
                                operation: 'Deposit'
                            );
                    
                    $transaction['Amount'] = $currrencyAmount;

                    return $transaction;
                },
            array: $this->transactions
        );

        [$deposits['Total_Qnt'], $deposits['Total_Value']] = [
            count(value: $deposits['Formatted']),
            $currencyFormatter->format(
                num: $depositsTotal = array_sum(array: $deposits['Original'])
            )
        ];

        [$withdrawls['Total_Qnt'], $withdrawls['Total_Value']] = [
            count(value: $withdrawls['Formatted']),
            $currencyFormatter->format(
                num: $withdrawlsTotal = array_sum(array: $withdrawls['Original'])
            )
        ];

        return [
            'Transactions' => [...$transactions],
            'Deposits' => [
                'Total_Qnt' => $deposits['Total_Qnt'],
                'Total_Value' => $deposits['Total_Value'],
                ...$deposits['Formatted']
            ],
            'Withdrawls' => [
                'Total_Qnt' => $withdrawls['Total_Qnt'],
                'Total_Value' => $withdrawls['Total_Value'],
                ...$withdrawls['Formatted']
            ],
            'Balance' => 
                $currencyFormatter->format(
                    num: (float) \bcsub(
                        num1: (string) $depositsTotal,
                        num2: (string) -$withdrawlsTotal,
                        scale: 2
                    )
                )
        ];
    }
}

function setJSON(array $transactions): string
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