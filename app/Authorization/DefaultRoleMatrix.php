<?php

declare(strict_types=1);

namespace App\Authorization;

use InvalidArgumentException;

final class DefaultRoleMatrix
{
    /**
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        $allPermissions = Permission::all();

        return [
            SystemRole::OWNER => $allPermissions,
            SystemRole::ADMIN => array_values(array_diff($allPermissions, [
                Permission::MANAGE_HOUSEHOLD,
            ])),
            SystemRole::MEMBER => [
                Permission::CREATE_EXPENSE,
                Permission::EDIT_OWN_EXPENSE,
                Permission::ADD_SETTLEMENT,
                Permission::COMPLETE_CHORE,
                Permission::MANAGE_SHOPPING,
                Permission::MANAGE_PINBOARD,
                Permission::VIEW_REPORTS,
            ],
            SystemRole::GUEST => [
                Permission::COMPLETE_CHORE,
                Permission::MANAGE_SHOPPING,
                Permission::MANAGE_PINBOARD,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionsFor(string $roleCode): array
    {
        $matrix = self::all();

        if (! isset($matrix[$roleCode])) {
            throw new InvalidArgumentException('Unsupported role code: ' . $roleCode);
        }

        return $matrix[$roleCode];
    }
}
