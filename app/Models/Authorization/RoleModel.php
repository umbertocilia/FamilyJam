<?php

declare(strict_types=1);

namespace App\Models\Authorization;

use App\Models\BaseModel;

final class RoleModel extends BaseModel
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'scope_household_id',
        'code',
        'name',
        'description',
        'is_system',
        'is_assignable',
    ];

    public function findByCode(string $code, ?int $householdId = null): ?array
    {
        $builder = $this->where('code', $code);

        if ($householdId === null) {
            $builder->where('household_id', null);
        } else {
            $builder->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
                ->groupEnd()
                ->orderBy('household_id', 'DESC');
        }

        return $builder->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAssignableForHousehold(int $householdId): array
    {
        return $this->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
            ->groupEnd()
            ->where('is_assignable', 1)
            ->orderBy('is_system', 'DESC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        return $this->select('roles.*, COUNT(DISTINCT membership_roles.membership_id) AS membership_count')
            ->join('membership_roles', 'membership_roles.role_id = roles.id', 'left')
            ->groupStart()
                ->where('roles.household_id', null)
                ->orWhere('roles.household_id', $householdId)
            ->groupEnd()
            ->where('roles.deleted_at', null)
            ->groupBy('roles.id')
            ->orderBy('roles.is_system', 'DESC')
            ->orderBy('roles.name', 'ASC')
            ->findAll();
    }

    public function findForHousehold(int $householdId, int $roleId): ?array
    {
        return $this->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
            ->groupEnd()
            ->where('id', $roleId)
            ->first();
    }

    public function codeExistsForHousehold(int $householdId, string $code, ?int $ignoreRoleId = null): bool
    {
        $builder = $this->withDeleted()
            ->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
            ->groupEnd()
            ->where('code', $code);

        if ($ignoreRoleId !== null) {
            $builder->where('id !=', $ignoreRoleId);
        }

        return $builder->countAllResults() > 0;
    }
}
