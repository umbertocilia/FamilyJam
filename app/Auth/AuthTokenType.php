<?php

declare(strict_types=1);

namespace App\Auth;

final class AuthTokenType
{
    public const EMAIL_VERIFICATION = 'email_verification';
    public const PASSWORD_RESET = 'password_reset';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::EMAIL_VERIFICATION,
            self::PASSWORD_RESET,
        ];
    }
}
