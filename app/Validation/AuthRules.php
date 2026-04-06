<?php

declare(strict_types=1);

namespace App\Validation;

final class AuthRules
{
    public function valid_timezone(?string $value): bool
    {
        return $value !== null && in_array($value, timezone_identifiers_list(), true);
    }

    public function valid_theme(?string $value): bool
    {
        return $value !== null && in_array($value, ['system', 'light', 'dark'], true);
    }

    public function strong_password(?string $value): bool
    {
        if ($value === null || strlen($value) < 10) {
            return false;
        }

        return preg_match('/[a-z]/', $value) === 1
            && preg_match('/[A-Z]/', $value) === 1
            && preg_match('/\d/', $value) === 1;
    }
}
