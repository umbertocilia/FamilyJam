<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\TenantScopedModel;

final class ExpenseModel extends TenantScopedModel
{
    protected $table = 'expenses';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'recurring_rule_id',
        'category_id',
        'expense_group_id',
        'receipt_attachment_id',
        'title',
        'description',
        'expense_date',
        'currency',
        'total_amount',
        'split_method',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * @param array{category_id?: int|string|null, expense_group_id?: int|string|null, month?: string|null, member_id?: int|string|null, status?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId, array $filters = []): array
    {
        helper('ui');
        $builder = (($filters['status'] ?? null) === 'deleted' ? $this->withDeleted() : $this)->select([
                'expenses.*',
                'expense_categories.name AS category_name',
                'expense_categories.color AS category_color',
                'expense_categories.code AS category_code',
                'expense_categories.is_system AS category_is_system',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
                'creator.display_name AS created_by_name',
                'updater.display_name AS updated_by_name',
                'receipt.original_name AS receipt_original_name',
            ])
            ->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left')
            ->join('expense_groups', 'expense_groups.id = expenses.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->join('users AS creator', 'creator.id = expenses.created_by', 'left')
            ->join('users AS updater', 'updater.id = expenses.updated_by', 'left')
            ->join('attachments AS receipt', 'receipt.id = expenses.receipt_attachment_id AND receipt.deleted_at IS NULL', 'left')
            ->where('expenses.household_id', $householdId);

        if (($filters['category_id'] ?? null) !== null && (string) $filters['category_id'] !== '') {
            $builder->where('expenses.category_id', (int) $filters['category_id']);
        }

        if (($filters['expense_group_id'] ?? null) !== null && (string) $filters['expense_group_id'] !== '') {
            $builder->where('expenses.expense_group_id', (int) $filters['expense_group_id']);
        }

        if (($filters['month'] ?? null) !== null && (string) $filters['month'] !== '') {
            $builder->where("DATE_FORMAT(expenses.expense_date, '%Y-%m') =", (string) $filters['month']);
        }

        if (($filters['member_id'] ?? null) !== null && (string) $filters['member_id'] !== '') {
            $memberId = (int) $filters['member_id'];
            $builder->groupStart()
                ->whereIn('expenses.id', static function ($subQuery) use ($memberId): void {
                    $subQuery->select('expense_payers.expense_id')
                        ->from('expense_payers')
                        ->where('expense_payers.user_id', $memberId);
                })
                ->orWhereIn('expenses.id', static function ($subQuery) use ($memberId): void {
                    $subQuery->select('expense_splits.expense_id')
                        ->from('expense_splits')
                        ->where('expense_splits.user_id', $memberId)
                        ->where('expense_splits.is_excluded', 0);
                })
                ->groupEnd();
        }

        if (($filters['status'] ?? null) !== null && (string) $filters['status'] !== '') {
            $builder->where('expenses.status', (string) $filters['status']);
        } else {
            $builder->whereIn('expenses.status', ['active', 'edited', 'disputed', 'posted']);
        }

        $rows = $builder
            ->orderBy('expenses.expense_date', 'DESC')
            ->orderBy('expenses.created_at', 'DESC')
            ->findAll();

        return array_map([$this, 'localizeCategory'], $rows);
    }

    public function findDetailForHousehold(int $householdId, int $expenseId, bool $includeDeleted = true): ?array
    {
        helper('ui');
        $builder = ($includeDeleted ? $this->withDeleted() : $this)->select([
                'expenses.*',
                'expense_categories.name AS category_name',
                'expense_categories.color AS category_color',
                'expense_categories.icon AS category_icon',
                'expense_categories.code AS category_code',
                'expense_categories.is_system AS category_is_system',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
                'creator.display_name AS created_by_name',
                'updater.display_name AS updated_by_name',
                'receipt.original_name AS receipt_original_name',
                'receipt.mime_type AS receipt_mime_type',
                'receipt.path AS receipt_path',
                'receipt.disk AS receipt_disk',
            ])
            ->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left')
            ->join('expense_groups', 'expense_groups.id = expenses.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->join('users AS creator', 'creator.id = expenses.created_by', 'left')
            ->join('users AS updater', 'updater.id = expenses.updated_by', 'left')
            ->join('attachments AS receipt', 'receipt.id = expenses.receipt_attachment_id AND receipt.deleted_at IS NULL', 'left');

        $row = $builder
            ->where('expenses.household_id', $householdId)
            ->where('expenses.id', $expenseId)
            ->first();

        return $row === null ? null : $this->localizeCategory($row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLedgerEligibleForHousehold(int $householdId): array
    {
        return $this->select([
                'expenses.*',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
            ])
            ->join('expense_groups', 'expense_groups.id = expenses.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->where('expenses.household_id', $householdId)
            ->where('expenses.deleted_at', null)
            ->whereIn('expenses.status', ['active', 'edited', 'disputed', 'posted'])
            ->orderBy('expenses.expense_date', 'ASC')
            ->orderBy('expenses.id', 'ASC')
            ->findAll();
    }

    public function findByRecurringRuleAndDate(int $recurringRuleId, string $expenseDate): ?array
    {
        return $this->withDeleted()
            ->where('recurring_rule_id', $recurringRuleId)
            ->where('expense_date', $expenseDate)
            ->orderBy('deleted_at', 'ASC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function localizeCategory(array $row): array
    {
        $row['category_name'] = ui_category_name(
            isset($row['category_code']) ? (string) $row['category_code'] : null,
            isset($row['category_name']) ? (string) $row['category_name'] : null,
            ! empty($row['category_is_system']),
        );

        return $row;
    }
}
