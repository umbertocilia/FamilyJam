<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\BaseModel;

final class ExpensePayerModel extends BaseModel
{
    protected $table = 'expense_payers';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'expense_id',
        'user_id',
        'amount_paid',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForExpense(int $expenseId): array
    {
        return $this->select('expense_payers.*, users.display_name, users.email')
            ->join('users', 'users.id = expense_payers.user_id', 'inner')
            ->where('expense_payers.expense_id', $expenseId)
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

        $rows = $this->select('expense_payers.*, users.display_name, users.email')
            ->join('users', 'users.id = expense_payers.user_id', 'inner')
            ->whereIn('expense_payers.expense_id', $expenseIds)
            ->orderBy('expense_payers.expense_id', 'ASC')
            ->orderBy('expense_payers.user_id', 'ASC')
            ->findAll();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['expense_id']][] = $row;
        }

        return $grouped;
    }
}
