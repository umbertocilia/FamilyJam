<?php

declare(strict_types=1);

namespace App\Models\Chores;

use App\Models\TenantScopedModel;

final class ChoreModel extends TenantScopedModel
{
    protected $table = 'chores';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'recurring_rule_id',
        'title',
        'description',
        'assignment_mode',
        'fixed_assignee_user_id',
        'rotation_anchor_user_id',
        'points',
        'estimated_minutes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @param array{assignment_mode?: string|null, is_active?: string|int|null} $filters
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId, array $filters = []): array
    {
        $builder = $this->select([
                'chores.*',
                'fixed_user.display_name AS fixed_assignee_name',
                'rotation_user.display_name AS rotation_anchor_name',
                'creator.display_name AS created_by_name',
                'recurring_rules.frequency AS recurring_frequency',
                'recurring_rules.interval_value AS recurring_interval_value',
                'recurring_rules.by_weekday AS recurring_by_weekday',
                'recurring_rules.day_of_month AS recurring_day_of_month',
                'recurring_rules.starts_at AS recurring_starts_at',
                'recurring_rules.ends_at AS recurring_ends_at',
                'recurring_rules.next_run_at AS recurring_next_run_at',
                'recurring_rules.is_active AS recurring_is_active',
                'recurring_rules.config_json AS recurring_config_json',
            ])
            ->join('users AS fixed_user', 'fixed_user.id = chores.fixed_assignee_user_id', 'left')
            ->join('users AS rotation_user', 'rotation_user.id = chores.rotation_anchor_user_id', 'left')
            ->join('users AS creator', 'creator.id = chores.created_by', 'left')
            ->join('recurring_rules', 'recurring_rules.id = chores.recurring_rule_id AND recurring_rules.deleted_at IS NULL', 'left')
            ->where('chores.household_id', $householdId)
            ->where('chores.deleted_at', null);

        if (($filters['assignment_mode'] ?? null) !== null && (string) $filters['assignment_mode'] !== '') {
            $builder->where('chores.assignment_mode', (string) $filters['assignment_mode']);
        }

        if (($filters['is_active'] ?? null) !== null && (string) $filters['is_active'] !== '') {
            $builder->where('chores.is_active', (int) $filters['is_active']);
        }

        return $builder
            ->orderBy('chores.is_active', 'DESC')
            ->orderBy('chores.title', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActiveForHousehold(int $householdId): array
    {
        return $this->where('household_id', $householdId)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('title', 'ASC')
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $choreId): ?array
    {
        return $this->select([
                'chores.*',
                'fixed_user.display_name AS fixed_assignee_name',
                'rotation_user.display_name AS rotation_anchor_name',
                'creator.display_name AS created_by_name',
                'updater.display_name AS updated_by_name',
                'recurring_rules.frequency AS recurring_frequency',
                'recurring_rules.interval_value AS recurring_interval_value',
                'recurring_rules.by_weekday AS recurring_by_weekday',
                'recurring_rules.day_of_month AS recurring_day_of_month',
                'recurring_rules.starts_at AS recurring_starts_at',
                'recurring_rules.ends_at AS recurring_ends_at',
                'recurring_rules.next_run_at AS recurring_next_run_at',
                'recurring_rules.last_run_at AS recurring_last_run_at',
                'recurring_rules.is_active AS recurring_is_active',
                'recurring_rules.config_json AS recurring_config_json',
            ])
            ->join('users AS fixed_user', 'fixed_user.id = chores.fixed_assignee_user_id', 'left')
            ->join('users AS rotation_user', 'rotation_user.id = chores.rotation_anchor_user_id', 'left')
            ->join('users AS creator', 'creator.id = chores.created_by', 'left')
            ->join('users AS updater', 'updater.id = chores.updated_by', 'left')
            ->join('recurring_rules', 'recurring_rules.id = chores.recurring_rule_id AND recurring_rules.deleted_at IS NULL', 'left')
            ->where('chores.household_id', $householdId)
            ->where('chores.id', $choreId)
            ->where('chores.deleted_at', null)
            ->first();
    }

    public function findByRecurringRule(int $householdId, int $recurringRuleId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('recurring_rule_id', $recurringRuleId)
            ->where('deleted_at', null)
            ->first();
    }
}
