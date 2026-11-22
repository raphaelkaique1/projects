<?php

declare(strict_types = 1);

namespace App\Enums;

enum HttpRequestMethod
{
    case QUERY;
    case HEAD;
    case GET;
    case POST;
    case PUT;
    case PATCH;
    case DELETE;

    public function __invoke(): string
    {
        return $this->name;
    }
}