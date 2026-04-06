<?php

declare(strict_types=1);

namespace App\Authorization;

final class Permission
{
    public const MANAGE_HOUSEHOLD = 'manage_household';
    public const MANAGE_MEMBERS = 'manage_members';
    public const MANAGE_ROLES = 'manage_roles';
    public const CREATE_EXPENSE = 'create_expense';
    public const EDIT_ANY_EXPENSE = 'edit_any_expense';
    public const EDIT_OWN_EXPENSE = 'edit_own_expense';
    public const DELETE_EXPENSE = 'delete_expense';
    public const ADD_SETTLEMENT = 'add_settlement';
    public const MANAGE_CHORES = 'manage_chores';
    public const COMPLETE_CHORE = 'complete_chore';
    public const MANAGE_SHOPPING = 'manage_shopping';
    public const MANAGE_PINBOARD = 'manage_pinboard';
    public const MANAGE_SETTINGS = 'manage_settings';
    public const VIEW_REPORTS = 'view_reports';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, array{name: string, module: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::MANAGE_HOUSEHOLD => [
                'name' => 'Manage household',
                'module' => 'households',
                'description' => 'Gestisce ownership, ciclo di vita e amministrazione dello spazio.',
            ],
            self::MANAGE_MEMBERS => [
                'name' => 'Manage members',
                'module' => 'memberships',
                'description' => 'Invita, sospende e aggiorna i membri del gruppo.',
            ],
            self::MANAGE_ROLES => [
                'name' => 'Manage roles',
                'module' => 'authorization',
                'description' => 'Configura ruoli household-specific e relative grant list.',
            ],
            self::CREATE_EXPENSE => [
                'name' => 'Create expense',
                'module' => 'expenses',
                'description' => 'Registra nuove spese condivise.',
            ],
            self::EDIT_ANY_EXPENSE => [
                'name' => 'Edit any expense',
                'module' => 'expenses',
                'description' => 'Modifica qualunque spesa del tenant.',
            ],
            self::EDIT_OWN_EXPENSE => [
                'name' => 'Edit own expense',
                'module' => 'expenses',
                'description' => 'Modifica soltanto le spese create dal membro.',
            ],
            self::DELETE_EXPENSE => [
                'name' => 'Delete expense',
                'module' => 'expenses',
                'description' => 'Rimuove o annulla spese condivise.',
            ],
            self::ADD_SETTLEMENT => [
                'name' => 'Add settlement',
                'module' => 'balances',
                'description' => 'Registra rimborsi e regolazioni di saldo.',
            ],
            self::MANAGE_CHORES => [
                'name' => 'Manage chores',
                'module' => 'chores',
                'description' => 'Crea e pianifica faccende, rotazioni e ricorrenze.',
            ],
            self::COMPLETE_CHORE => [
                'name' => 'Complete chore',
                'module' => 'chores',
                'description' => 'Marca una faccenda come completata.',
            ],
            self::MANAGE_SHOPPING => [
                'name' => 'Manage shopping',
                'module' => 'shopping',
                'description' => 'Gestisce liste della spesa condivise.',
            ],
            self::MANAGE_PINBOARD => [
                'name' => 'Manage pinboard',
                'module' => 'pinboard',
                'description' => 'Pubblica, aggiorna e modera contenuti della bacheca.',
            ],
            self::MANAGE_SETTINGS => [
                'name' => 'Manage settings',
                'module' => 'settings',
                'description' => 'Aggiorna preferenze operative del workspace.',
            ],
            self::VIEW_REPORTS => [
                'name' => 'View reports',
                'module' => 'reports',
                'description' => 'Consulta dashboard, riepiloghi e analytics household.',
            ],
        ];
    }
}
