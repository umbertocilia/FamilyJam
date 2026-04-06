<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Authorization\DefaultRoleMatrix;
use App\Authorization\Permission;
use App\Authorization\SystemRole;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

final class BaseRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = [];
        $permissionQuery = $this->db->table('permissions')->whereIn('code', Permission::all())->get();

        if ($permissionQuery === false) {
            $error = $this->db->error();
            throw new \RuntimeException('Seed role permissions failed while loading permissions: ' . ($error['message'] ?? 'unknown database error'));
        }

        foreach ($permissionQuery->getResultArray() as $permission) {
            $permissionIds[$permission['code']] = (int) $permission['id'];
        }

        $now = Time::now()->toDateTimeString();

        foreach (SystemRole::all() as $roleCode) {
            $roleQuery = $this->db->query(
                'SELECT `id` FROM `roles` WHERE `household_id` IS NULL AND `code` = ? LIMIT 1',
                [$roleCode],
            );

            if ($roleQuery === false) {
                $error = $this->db->error();
                throw new \RuntimeException('Seed role permissions failed while loading roles: ' . ($error['message'] ?? 'unknown database error'));
            }

            $role = $roleQuery->getRowArray();

            if ($role === null) {
                continue;
            }

            $this->db->table('role_permissions')->where('role_id', (int) $role['id'])->delete();

            foreach (DefaultRoleMatrix::permissionsFor($roleCode) as $permissionCode) {
                if (! array_key_exists($permissionCode, $permissionIds)) {
                    continue;
                }

                $this->db->table('role_permissions')->insert([
                    'role_id' => (int) $role['id'],
                    'permission_id' => $permissionIds[$permissionCode],
                    'created_at' => $now,
                ]);
            }
        }
    }
}
