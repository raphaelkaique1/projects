<?php

declare(strict_types = 1);

namespace App\Enums;

use App\Exceptions\HttpStatusCodeException;

enum HttpStatusCode: int
{
    case OK = 200;
    case BAD_REQUEST = 400;
    case NOT_FOUND = 404;
    case NOT_IMPLEMENTED =  501;

    public function __invoke(): array
    {
        return [
            'message' => $this->name
                |> str_replace(
                        search: '_',
                        replace: ' ',
                        ...
                    )
                |> \mb_convert_case(
                    ...,
                    mode: MB_CASE_TITLE
                ) ?? throw new HttpStatusCodeException(
                    message: 'Invalid http status response.'
                ),
            'code'=> $this->value ?? throw new HttpStatusCodeException(
                    message: 'Invalid http status code.'
                )
        ];
    }
}