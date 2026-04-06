<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\TenantScopedModel;

final class ExpenseGroupModel extends TenantScopedModel
{
    protected $table = 'expense_groups';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'name',
        'description',
        'color',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        return $this->where('household_id', $householdId)
            ->where('deleted_at', null)
            ->orderBy('is_active', 'DESC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function findForHousehold(int $householdId, int $groupId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('id', $groupId)
            ->where('deleted_at', null)
            ->first();
    }
}
