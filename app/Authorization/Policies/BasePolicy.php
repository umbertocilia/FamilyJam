<?php

declare(strict_types=1);

namespace App\Authorization\Policies;

abstract class BasePolicy
{
    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     */
    protected function hasPermission(array $context, string $permission): bool
    {
        return in_array($permission, $context['permissions'], true);
    }

    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     */
    protected function hasRole(array $context, string $roleCode): bool
    {
        return in_array($roleCode, $context['role_codes'], true);
    }
}
