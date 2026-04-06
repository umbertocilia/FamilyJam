<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\TenantScopedModel;

final class RecurringRuleModel extends TenantScopedModel
{
    protected $table = 'recurring_rules';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'entity_type',
        'frequency',
        'interval_value',
        'by_weekday',
        'day_of_month',
        'starts_at',
        'ends_at',
        'next_run_at',
        'last_run_at',
        'is_active',
        'config_json',
        'created_by',
        'updated_by',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listExpenseRulesForHousehold(int $householdId): array
    {
        return $this->select([
                'recurring_rules.*',
                'creator.display_name AS created_by_name',
                'creator.email AS created_by_email',
                'updater.display_name AS updated_by_name',
            ])
            ->join('users AS creator', 'creator.id = recurring_rules.created_by', 'left')
            ->join('users AS updater', 'updater.id = recurring_rules.updated_by', 'left')
            ->where('recurring_rules.household_id', $householdId)
            ->where('recurring_rules.entity_type', 'expense')
            ->orderBy('recurring_rules.is_active', 'DESC')
            ->orderBy('recurring_rules.next_run_at', 'ASC')
            ->orderBy('recurring_rules.created_at', 'DESC')
            ->findAll();
    }

    public function findExpenseRuleForHousehold(int $householdId, int $ruleId): ?array
    {
        return $this->select([
                'recurring_rules.*',
                'creator.display_name AS created_by_name',
                'creator.email AS created_by_email',
                'updater.display_name AS updated_by_name',
            ])
            ->join('users AS creator', 'creator.id = recurring_rules.created_by', 'left')
            ->join('users AS updater', 'updater.id = recurring_rules.updated_by', 'left')
            ->where('recurring_rules.household_id', $householdId)
            ->where('recurring_rules.entity_type', 'expense')
            ->where('recurring_rules.id', $ruleId)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDueExpenseRules(string $dueAt, int $limit = 100): array
    {
        return $this->listDueRulesForEntity('expense', $dueAt, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDueRulesForEntity(string $entityType, string $dueAt, int $limit = 100): array
    {
        return $this->where('entity_type', $entityType)
            ->where('is_active', 1)
            ->where('next_run_at <=', $dueAt)
            ->where('deleted_at', null)
            ->orderBy('next_run_at', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function findRuleForHousehold(int $householdId, string $entityType, int $ruleId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('entity_type', $entityType)
            ->where('id', $ruleId)
            ->first();
    }
}
