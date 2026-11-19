<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case EmailVerification = 'email_verification';
    case PasswordReset = 'password_reset';
    case EmailChange = 'email_change';

    /**
     * Short phrase used in the email, for example "to verify your email".
     */
    public function reason(): string
    {
        return match ($this) {
            self::EmailVerification => 'verify your email address',
            self::PasswordReset => 'reset your password',
            self::EmailChange => 'confirm your new email address',
        };
    }
}
