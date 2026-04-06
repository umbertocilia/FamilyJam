<?php

declare(strict_types=1);

namespace App\Authorization\Policies;

use App\Authorization\Permission;

final class RolePolicy extends BasePolicy
{
    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     */
    public function manageRoles(array $context): bool
    {
        return $this->hasPermission($context, Permission::MANAGE_ROLES);
    }

    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     * @param array<string, mixed>|null $role
     */
    public function update(array $context, ?array $role): bool
    {
        if (! $this->manageRoles($context) || $role === null) {
            return false;
        }

        return empty($role['is_system']) && (int) ($role['household_id'] ?? 0) === (int) $context['membership']['household_id'];
    }
}
