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
    private array $locales = [
        'documentLocale' => '',
        'formatLocale' => ''
    ];

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
        $this->locales['documentLocale'] =
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

    public function format(string $formatLocale): self
    {
        $this->locales['formatLocale'] =
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

        $this->transactions = [
            'Transactions' => [...$transactions]
        ];

        return $this;
    }

    public function totals(): array
    {
        $deposits = [];
        $withdrawls = [];

        $floatArrayReduce = fn(
                array $floatToStringArray,
                string $operation
            ): string => array_reduce(
                    array: $floatToStringArray,
                    callback: fn(
                            string $accumulator,
                            string $newValue
                        ): string => $operation(
                            num1: (string) $accumulator,
                            num2: (string) $newValue,
                            scale: 2
                        ),
                    initial: '0'
                );

        $formatter = new NumberFormatter(
                locale: $this->locales['formatLocale'],
                style: NumberFormatter::DECIMAL
            );

        $currentCurrency = new NumberFormatter(
                locale: $this->locales['documentLocale'],
                style: NumberFormatter::CURRENCY
            )
            ->getTextAttribute(
                attribute: NumberFormatter::CURRENCY_CODE
            );

        foreach (
            [
                NumberFormatter::MIN_FRACTION_DIGITS,
                NumberFormatter::MAX_FRACTION_DIGITS
            ] as $attribute
        ) {
            $formatter->setAttribute($attribute, 2);
        }

        array_map(
            callback: function (
                    array $transaction
                ) use (&$deposits, &$withdrawls, &$formatter): void {
                    $save = function (
                        array &$list
                    ) use (&$transaction, &$formatter): void {
                        $list['Values'][] = (string) $formatter->parse(
                                string: $transaction['Amount']
                            );

                        $list[$transaction['Description']] = [
                                'Date' => $transaction['Date'],
                                'Check' => $transaction['Check'],
                                'Amount' => $transaction['Amount'],
                                'Currency' => $transaction['Currency']
                            ];
                    };

                    $transaction['Operation'] === 'Deposit'
                        ? $save(list: $deposits)
                        : $save(list: $withdrawls);
                },
            array: $this->transactions['Transactions']
        );

        return [
            'Deposits' => [
                'TotalQnt' => count(value: $deposits['Values']),
                'TotalValue' => $formatter->format(
                    num: (float) $depositsTotalValue = $floatArrayReduce(
                        floatToStringArray: array_shift(array: $deposits),
                        operation: '\bcadd'
                    )
                ),
                'Currency' => $currentCurrency,
                'Transactions' => $deposits
            ],
            'Withdrawals' => [
                'TotalQnt' => count(value: $withdrawls['Values']),
                'TotalValue' => $formatter->format(
                    num: (float) $withdrawlsTotalValue = $floatArrayReduce(
                        floatToStringArray: array_shift(array: $withdrawls),
                        operation: '\bcadd'
                    )
                ),
                'Currency' => $currentCurrency,
                'Transactions' => $withdrawls
            ],
            'Balance' => [
                'Total' => $formatter->format(
                    num: (float) \bcadd(
                        num1: $depositsTotalValue,
                        num2: $withdrawlsTotalValue,
                        scale: 2
                    )
                ),
                'Currency' => $currentCurrency
            ]
        ];
    }
}
