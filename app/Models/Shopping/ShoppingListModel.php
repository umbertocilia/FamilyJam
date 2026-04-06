<?php

declare(strict_types=1);

namespace App\Models\Shopping;

use App\Models\TenantScopedModel;

final class ShoppingListModel extends TenantScopedModel
{
    protected $table = 'shopping_lists';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'name',
        'is_default',
        'created_by',
        'deleted_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        return $this->select([
                'shopping_lists.*',
                'creator.display_name AS created_by_name',
                'COUNT(shopping_items.id) AS items_count',
                'SUM(CASE WHEN shopping_items.is_purchased = 0 THEN 1 ELSE 0 END) AS open_count',
                'SUM(CASE WHEN shopping_items.is_purchased = 1 THEN 1 ELSE 0 END) AS purchased_count',
                'SUM(CASE WHEN shopping_items.priority = "urgent" AND shopping_items.is_purchased = 0 THEN 1 ELSE 0 END) AS urgent_open_count',
            ])
            ->join('users AS creator', 'creator.id = shopping_lists.created_by', 'left')
            ->join('shopping_items', 'shopping_items.shopping_list_id = shopping_lists.id AND shopping_items.deleted_at IS NULL', 'left')
            ->where('shopping_lists.household_id', $householdId)
            ->where('shopping_lists.deleted_at', null)
            ->groupBy('shopping_lists.id')
            ->orderBy('shopping_lists.is_default', 'DESC')
            ->orderBy('shopping_lists.name', 'ASC')
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $listId, bool $withDeleted = false): ?array
    {
        $builder = $this->select([
                'shopping_lists.*',
                'creator.display_name AS created_by_name',
                'COUNT(shopping_items.id) AS items_count',
                'SUM(CASE WHEN shopping_items.is_purchased = 0 THEN 1 ELSE 0 END) AS open_count',
                'SUM(CASE WHEN shopping_items.is_purchased = 1 THEN 1 ELSE 0 END) AS purchased_count',
                'SUM(CASE WHEN shopping_items.priority = "urgent" AND shopping_items.is_purchased = 0 THEN 1 ELSE 0 END) AS urgent_open_count',
            ])
            ->join('users AS creator', 'creator.id = shopping_lists.created_by', 'left')
            ->join('shopping_items', 'shopping_items.shopping_list_id = shopping_lists.id AND shopping_items.deleted_at IS NULL', 'left')
            ->where('shopping_lists.household_id', $householdId)
            ->where('shopping_lists.id', $listId)
            ->groupBy('shopping_lists.id');

        if ($withDeleted) {
            $builder->withDeleted();
        } else {
            $builder->where('shopping_lists.deleted_at', null);
        }

        return $builder->first();
    }

    public function findDefaultForHousehold(int $householdId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('is_default', 1)
            ->where('deleted_at', null)
            ->first();
    }

    public function clearDefaultForHousehold(int $householdId, ?int $excludeListId = null): void
    {
        $builder = $this->builder()
            ->where('household_id', $householdId)
            ->where('deleted_at', null);

        if ($excludeListId !== null) {
            $builder->where('id !=', $excludeListId);
        }

        $builder->set(['is_default' => 0])->update();
    }
}
