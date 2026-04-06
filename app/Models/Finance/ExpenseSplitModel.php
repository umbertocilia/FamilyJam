<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\BaseModel;

final class ExpenseSplitModel extends BaseModel
{
    protected $table = 'expense_splits';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'expense_id',
        'user_id',
        'owed_amount',
        'percentage',
        'share_units',
        'is_excluded',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForExpense(int $expenseId): array
    {
        return $this->select('expense_splits.*, users.display_name, users.email')
            ->join('users', 'users.id = expense_splits.user_id', 'inner')
            ->where('expense_splits.expense_id', $expenseId)
            ->orderBy('users.display_name', 'ASC')
            ->findAll();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function replaceForExpense(int $expenseId, array $rows): void
    {
        $this->builder()->where('expense_id', $expenseId)->delete();

        foreach ($rows as $row) {
            $row['expense_id'] = $expenseId;
            $this->insert($row);
        }
    }

    /**
     * @param list<int> $expenseIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function listForExpenseIds(array $expenseIds): array
    {
        if ($expenseIds === []) {
            return [];
        }

        $rows = $this->select('expense_splits.*, users.display_name, users.email')
            ->join('users', 'users.id = expense_splits.user_id', 'inner')
            ->whereIn('expense_splits.expense_id', $expenseIds)
            ->orderBy('expense_splits.expense_id', 'ASC')
            ->orderBy('expense_splits.user_id', 'ASC')
            ->findAll();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['expense_id']][] = $row;
        }

        return $grouped;
    }
}
