<?php

declare(strict_types=1);

namespace App\Models\Households;

use App\Models\BaseModel;

final class MembershipRoleModel extends BaseModel
{
    protected $table = 'membership_roles';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'membership_id',
        'role_id',
        'assigned_by_user_id',
        'created_at',
    ];

    /**
     * @return list<int>
     */
    public function findRoleIdsByMembershipId(int $membershipId): array
    {
        $rows = $this->select('role_id')
            ->where('membership_id', $membershipId)
            ->findAll();

        return array_values(array_map(
            static fn (array $row): int => (int) $row['role_id'],
            $rows,
        ));
    }

    public function hasRole(int $membershipId, int $roleId): bool
    {
        return $this->where('membership_id', $membershipId)
            ->where('role_id', $roleId)
            ->countAllResults() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRolesForMembership(int $membershipId): array
    {
        return $this->select('roles.*')
            ->join('roles', 'roles.id = membership_roles.role_id', 'inner')
            ->where('membership_roles.membership_id', $membershipId)
            ->where('roles.deleted_at', null)
            ->orderBy('roles.is_system', 'DESC')
            ->orderBy('roles.name', 'ASC')
            ->findAll();
    }

    /**
     * @return list<string>
     */
    public function findRoleCodesByMembershipId(int $membershipId): array
    {
        $rows = $this->select('roles.code')
            ->join('roles', 'roles.id = membership_roles.role_id', 'inner')
            ->where('membership_roles.membership_id', $membershipId)
            ->where('roles.deleted_at', null)
            ->findAll();

        return array_values(array_map(
            static fn (array $row): string => (string) $row['code'],
            $rows,
        ));
    }

    /**
     * @param list<int> $roleIds
     */
    public function syncRoles(int $membershipId, array $roleIds, ?int $assignedByUserId = null): void
    {
        $this->builder()
            ->where('membership_id', $membershipId)
            ->delete();

        foreach ($roleIds as $roleId) {
            $this->insert([
                'membership_id' => $membershipId,
                'role_id' => $roleId,
                'assigned_by_user_id' => $assignedByUserId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
