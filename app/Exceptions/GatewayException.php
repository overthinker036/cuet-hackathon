<?php

namespace App\Exceptions;

use Exception;

class GatewayException extends Exception
{
    public function __construct(string $message = "Gateway error", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}