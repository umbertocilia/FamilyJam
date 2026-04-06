<?php

declare(strict_types=1);

namespace App\Models\Authorization;

use App\Models\BaseModel;

final class RolePermissionModel extends BaseModel
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'role_id',
        'permission_id',
        'created_at',
    ];

    /**
     * @return list<string>
     */
    public function findPermissionCodesByRoleId(int $roleId): array
    {
        return $this->findPermissionCodesByRoleIds([$roleId]);
    }

    /**
     * @param list<int> $roleIds
     * @return list<string>
     */
    public function findPermissionCodesByRoleIds(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $rows = $this->select('permissions.code')
            ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->groupBy('permissions.code')
            ->findAll();

        return array_values(array_map(
            static fn (array $row): string => $row['code'],
            $rows,
        ));
    }

    /**
     * @return list<int>
     */
    public function findPermissionIdsByRoleId(int $roleId): array
    {
        $rows = $this->select('permission_id')
            ->where('role_id', $roleId)
            ->findAll();

        return array_values(array_map(
            static fn (array $row): int => (int) $row['permission_id'],
            $rows,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPermissionDetailsByRoleId(int $roleId): array
    {
        return $this->select('permissions.*')
            ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
            ->where('role_permissions.role_id', $roleId)
            ->orderBy('permissions.module', 'ASC')
            ->orderBy('permissions.name', 'ASC')
            ->findAll();
    }

    /**
     * @param list<int> $permissionIds
     */
    public function syncPermissionIds(int $roleId, array $permissionIds): void
    {
        $this->builder()
            ->where('role_id', $roleId)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            $this->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
