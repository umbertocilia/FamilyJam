<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\BaseModel;

final class ExpenseCategoryModel extends BaseModel
{
    protected $table = 'expense_categories';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'scope_household_id',
        'code',
        'name',
        'color',
        'icon',
        'is_system',
        'sort_order',
        'is_active',
        'created_by',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAvailableForHousehold(int $householdId): array
    {
        helper('ui');

        $rows = $this->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
            ->groupEnd()
            ->where('is_active', 1)
            ->orderBy('is_system', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return array_map([$this, 'localizeCategory'], $rows);
    }

    public function findAvailableForHousehold(int $householdId, int $categoryId): ?array
    {
        helper('ui');

        $row = $this->groupStart()
                ->where('household_id', null)
                ->orWhere('household_id', $householdId)
            ->groupEnd()
            ->where('id', $categoryId)
            ->where('is_active', 1)
            ->first();

        return $row === null ? null : $this->localizeCategory($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function localizeCategory(array $row): array
    {
        $row['name'] = ui_category_name(
            isset($row['code']) ? (string) $row['code'] : null,
            isset($row['name']) ? (string) $row['name'] : null,
            ! empty($row['is_system'])
        );

        return $row;
    }
}
