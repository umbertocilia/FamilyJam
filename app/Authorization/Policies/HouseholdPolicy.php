<?php

declare(strict_types=1);

namespace App\Authorization\Policies;

use App\Authorization\Permission;

final class HouseholdPolicy extends BasePolicy
{
    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     */
    public function manageSettings(array $context): bool
    {
        return $this->hasPermission($context, Permission::MANAGE_SETTINGS);
    }
}
