<?php

declare(strict_types=1);

if (! function_exists('has_permission')) {
    /**
     * @param array<string, mixed>|null $activeHousehold
     */
    function has_permission(string $permission, ?array $activeHousehold = null, ?int $userId = null): bool
    {
        $resolvedUserId = $userId ?? (session()->get('auth.user_id') === null ? null : (int) session()->get('auth.user_id'));
        $householdIdentifier = $activeHousehold['household_slug'] ?? session()->get('app.active_household');

        if ($resolvedUserId === null || ! is_string($householdIdentifier) || $householdIdentifier === '') {
            return false;
        }

        return service('householdAuthorization')->hasPermission($resolvedUserId, $householdIdentifier, $permission);
    }
}

if (! function_exists('has_role')) {
    /**
     * @param array<string, mixed>|null $activeHousehold
     */
    function has_role(string $roleCode, ?array $activeHousehold = null, ?int $userId = null): bool
    {
        $resolvedUserId = $userId ?? (session()->get('auth.user_id') === null ? null : (int) session()->get('auth.user_id'));
        $householdIdentifier = $activeHousehold['household_slug'] ?? session()->get('app.active_household');

        if ($resolvedUserId === null || ! is_string($householdIdentifier) || $householdIdentifier === '') {
            return false;
        }

        return service('householdAuthorization')->hasRole($resolvedUserId, $householdIdentifier, $roleCode);
    }
}

if (! function_exists('can_manage')) {
    /**
     * @param array<string, mixed>|null $resource
     * @param array<string, mixed>|null $activeHousehold
     */
    function can_manage(string $ability, ?array $resource = null, ?array $activeHousehold = null, ?int $userId = null): bool
    {
        $resolvedUserId = $userId ?? (session()->get('auth.user_id') === null ? null : (int) session()->get('auth.user_id'));
        $householdIdentifier = $activeHousehold['household_slug'] ?? session()->get('app.active_household');

        if ($resolvedUserId === null || ! is_string($householdIdentifier) || $householdIdentifier === '') {
            return false;
        }

        return service('householdAuthorization')->canManage($resolvedUserId, $householdIdentifier, $ability, $resource);
    }
}
