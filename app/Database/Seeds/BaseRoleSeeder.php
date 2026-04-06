<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Authorization\SystemRole;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

final class BaseRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        foreach (SystemRole::definitions() as $code => $definition) {
            $query = $this->db->query(
                'SELECT `id` FROM `roles` WHERE `household_id` IS NULL AND `code` = ? LIMIT 1',
                [$code],
            );

            if ($query === false) {
                $error = $this->db->error();
                throw new \RuntimeException('Seed roles failed: ' . ($error['message'] ?? 'unknown database error'));
            }

            $existing = $query->getRowArray();

            $payload = [
                'household_id' => null,
                'scope_household_id' => 0,
                'code' => $code,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_system' => 1,
                'is_assignable' => $code === SystemRole::OWNER ? 0 : 1,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($existing === null) {
                $payload['created_at'] = $now;
                $this->db->table('roles')->insert($payload);
                continue;
            }

            $this->db->table('roles')->where('id', (int) $existing['id'])->update($payload);
        }
    }
}
