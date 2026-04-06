<?php

declare(strict_types=1);

namespace App\Authorization;

final class SystemRole
{
    public const OWNER = 'owner';
    public const ADMIN = 'admin';
    public const MEMBER = 'member';
    public const GUEST = 'guest';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::OWNER,
            self::ADMIN,
            self::MEMBER,
            self::GUEST,
        ];
    }

    /**
     * @return array<string, array{name: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::OWNER => [
                'name' => 'Owner',
                'description' => 'Controllo completo dello spazio household e dei permessi.',
            ],
            self::ADMIN => [
                'name' => 'Admin',
                'description' => 'Gestione operativa del workspace con limiti sulla governance finale.',
            ],
            self::MEMBER => [
                'name' => 'Member',
                'description' => 'Contribuisce a spese, bacheca, chores e shopping condivisi.',
            ],
            self::GUEST => [
                'name' => 'Guest',
                'description' => 'Accesso limitato a task e strumenti collaborativi essenziali.',
            ],
        ];
    }
}
