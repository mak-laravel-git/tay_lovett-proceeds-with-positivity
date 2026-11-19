<?php

namespace App\Enums;

enum OtpStatus: string
{
    case Verified = 'verified';
    case Invalid = 'invalid';
    case Expired = 'expired';
    case TooManyAttempts = 'too_many_attempts';
    case Missing = 'missing';

    public function message(): string
    {
        return match ($this) {
            self::Verified => 'Code accepted.',
            self::Invalid => 'That code is not correct. Check it and try again.',
            self::Expired => 'That code has expired. Request a new one.',
            self::TooManyAttempts => 'Too many wrong attempts. Request a new code.',
            self::Missing => 'No active code. Request a new one.',
        };
    }
}
