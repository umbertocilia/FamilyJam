<?php

declare(strict_types=1);

namespace App\Validation;

use App\Authorization\Permission;

final class AppRules
{
    public function valid_currency_code(?string $value): bool
    {
        return $value !== null && preg_match('/^[A-Z]{3}$/', strtoupper($value)) === 1;
    }

    public function valid_role_code(?string $value): bool
    {
        return $value !== null && preg_match('/^[a-z0-9_]+$/', $value) === 1;
    }

    public function valid_permission_code(?string $value): bool
    {
        return $value !== null && in_array($value, Permission::all(), true);
    }
}
