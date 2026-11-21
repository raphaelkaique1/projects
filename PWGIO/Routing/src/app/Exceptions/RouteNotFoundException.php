<?php

declare(strict_types = 1);

namespace App\Exceptions;

final class RouteNotFoundException extends \RuntimeException
{
    protected $code = 404;
    protected $message = 'Not Found';
}
