<?php

namespace App\Exceptions;

use Exception;

class OtpThrottledException extends Exception
{
    public function __construct(public readonly int $retryAfterSeconds, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'Please wait '.$retryAfterSeconds.' seconds before requesting another code.');
    }
}
