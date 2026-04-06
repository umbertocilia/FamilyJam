<?php

declare(strict_types=1);

if (! function_exists('active_household_slug')) {
    /**
     * @param array<string, mixed>|null $activeHousehold
     */
    function active_household_slug(?array $activeHousehold): ?string
    {
        return $activeHousehold['household_slug'] ?? null;
    }
}

if (! function_exists('active_household_name')) {
    /**
     * @param array<string, mixed>|null $activeHousehold
     */
    function active_household_name(?array $activeHousehold, string $fallback = 'Select household'): string
    {
        helper('ui');

        if ($fallback === 'Select household') {
            $fallback = ui_locale() === 'it' ? 'Seleziona casa' : 'Select household';
        }

        return $activeHousehold['household_name'] ?? $fallback;
    }
}

if (! function_exists('tenant_route_or_households')) {
    /**
     * @param array<string, mixed>|null $activeHousehold
     */
    function tenant_route_or_households(string $routeName, ?array $activeHousehold): string
    {
        $householdSlug = active_household_slug($activeHousehold);

        if ($householdSlug === null) {
            return route_url('households.index');
        }

        return route_url($routeName, $householdSlug);
    }
}

if (! function_exists('membership_role_list')) {
    /**
     * @param array<string, mixed>|null $membership
     * @return list<string>
     */
    function membership_role_list(?array $membership): array
    {
        $roleCodes = $membership['role_codes'] ?? '';

        if (! is_string($roleCodes) || trim($roleCodes) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $roleCodes))));
    }
}
