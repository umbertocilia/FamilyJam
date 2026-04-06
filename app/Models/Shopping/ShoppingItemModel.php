<?php

declare(strict_types=1);

namespace App\Models\Shopping;

use App\Models\TenantScopedModel;

final class ShoppingItemModel extends TenantScopedModel
{
    protected $table = 'shopping_items';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'shopping_list_id',
        'household_id',
        'name',
        'quantity',
        'unit',
        'category',
        'notes',
        'priority',
        'assigned_user_id',
        'position',
        'is_purchased',
        'purchased_at',
        'purchased_by',
        'converted_expense_id',
        'created_by',
        'deleted_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForList(int $householdId, int $listId, ?bool $isPurchased = null): array
    {
        $builder = $this->baseDetailQuery()
            ->where('shopping_items.household_id', $householdId)
            ->where('shopping_items.shopping_list_id', $listId)
            ->where('shopping_items.deleted_at', null);

        if ($isPurchased !== null) {
            $builder->where('shopping_items.is_purchased', $isPurchased ? 1 : 0);
        }

        return $builder
            ->orderBy('shopping_items.is_purchased', 'ASC')
            ->orderBy('FIELD(shopping_items.priority, "urgent", "high", "normal", "low")', '', false)
            ->orderBy('shopping_items.position', 'ASC')
            ->orderBy('shopping_items.id', 'ASC')
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $itemId, bool $withDeleted = false): ?array
    {
        $builder = $this->baseDetailQuery()
            ->where('shopping_items.household_id', $householdId)
            ->where('shopping_items.id', $itemId);

        if ($withDeleted) {
            $builder->withDeleted();
        } else {
            $builder->where('shopping_items.deleted_at', null);
        }

        return $builder->first();
    }

    public function nextPosition(int $listId): int
    {
        $row = $this->selectMax('position')
            ->where('shopping_list_id', $listId)
            ->where('deleted_at', null)
            ->first();

        return ((int) ($row['position'] ?? 0)) + 1;
    }

    /**
     * @param list<int> $itemIds
     * @return list<array<string, mixed>>
     */
    public function listPurchasedAvailableForConversion(int $householdId, int $listId, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return $this->baseDetailQuery()
            ->where('shopping_items.household_id', $householdId)
            ->where('shopping_items.shopping_list_id', $listId)
            ->whereIn('shopping_items.id', $itemIds)
            ->where('shopping_items.is_purchased', 1)
            ->where('shopping_items.converted_expense_id', null)
            ->where('shopping_items.deleted_at', null)
            ->orderBy('shopping_items.position', 'ASC')
            ->orderBy('shopping_items.id', 'ASC')
            ->findAll();
    }

    private function baseDetailQuery()
    {
        return $this->select([
                'shopping_items.*',
                'assigned.display_name AS assigned_user_name',
                'purchased.display_name AS purchased_by_name',
                'creator.display_name AS created_by_name',
                'shopping_lists.name AS shopping_list_name',
            ])
            ->join('shopping_lists', 'shopping_lists.id = shopping_items.shopping_list_id', 'inner')
            ->join('users AS assigned', 'assigned.id = shopping_items.assigned_user_id', 'left')
            ->join('users AS purchased', 'purchased.id = shopping_items.purchased_by', 'left')
            ->join('users AS creator', 'creator.id = shopping_items.created_by', 'left');
    }
}
