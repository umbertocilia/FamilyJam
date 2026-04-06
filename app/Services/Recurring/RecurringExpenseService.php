<?php

declare(strict_types=1);

namespace App\Services\Recurring;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Expenses\ExpenseValidationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use DomainException;

final class RecurringExpenseService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?ExpenseValidationService $expenseValidationService = null,
        private readonly ?RecurringScheduleService $recurringScheduleService = null,
        private readonly ?RecurringRuleModel $recurringRuleModel = null,
        private readonly ?ExpenseCategoryModel $expenseCategoryModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function listContext(int $actorUserId, string $identifier): ?array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $rules = ($this->recurringRuleModel ?? new RecurringRuleModel())->listExpenseRulesForHousehold((int) $context['household']['id']);

        return array_merge($context, [
            'rules' => array_map(fn (array $rule): array => $this->decorateRule($rule), $rules),
            'canCreateRecurring' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($actorUserId, $identifier, Permission::CREATE_EXPENSE),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formContext(int $actorUserId, string $identifier, ?int $ruleId = null): ?array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $rule = null;

        if ($ruleId !== null) {
            $rule = ($this->recurringRuleModel ?? new RecurringRuleModel())
                ->findExpenseRuleForHousehold((int) $context['household']['id'], $ruleId);

            if ($rule === null || ! $this->canManageRule($actorUserId, $identifier, $rule)) {
                return null;
            }

            $rule = $this->decorateRule($rule);
        }

        return array_merge($context, [
            'rule' => $rule,
            'categories' => ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->listAvailableForHousehold((int) $context['household']['id']),
            'members' => $context['members'],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $actorUserId, string $identifier, array $payload): array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($actorUserId, $identifier, Permission::CREATE_EXPENSE)) {
            throw new DomainException('Non hai i permessi necessari per creare una recurring expense.');
        }

        $normalized = $this->validateRecurringPayload((int) $context['household']['id'], $payload, $context['members']);
        $db = $this->db ?? Database::connect();
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();

        $ruleId = $ruleModel->insert([
            'household_id' => (int) $context['household']['id'],
            'entity_type' => 'expense',
            'frequency' => $normalized['schedule']['frequency'],
            'interval_value' => $normalized['schedule']['interval_value'],
            'by_weekday' => $normalized['schedule']['by_weekday_json'],
            'day_of_month' => $normalized['schedule']['day_of_month'],
            'starts_at' => $normalized['schedule']['starts_at'],
            'ends_at' => $normalized['schedule']['ends_at'],
            'next_run_at' => $normalized['schedule']['next_run_at'],
            'last_run_at' => null,
            'is_active' => $normalized['schedule']['is_active'],
            'config_json' => json_encode($normalized['config'], JSON_THROW_ON_ERROR),
            'created_by' => $actorUserId,
            'updated_by' => $actorUserId,
        ], true);

        $rule = $ruleModel->findExpenseRuleForHousehold((int) $context['household']['id'], (int) $ruleId) ?? [];

        $audit->record(
            action: 'recurring_rule.created',
            entityType: 'recurring_rule',
            entityId: (int) $ruleId,
            actorUserId: $actorUserId,
            householdId: (int) $context['household']['id'],
            after: $rule,
        );

        $db->transComplete();

        return $this->decorateRule($rule);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $actorUserId, string $identifier, int $ruleId, array $payload): array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            throw new DomainException('Recurring rule non disponibile.');
        }

        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel();
        $existing = $ruleModel->findExpenseRuleForHousehold((int) $context['household']['id'], $ruleId);

        if ($existing === null || ! $this->canManageRule($actorUserId, $identifier, $existing)) {
            throw new DomainException('Recurring rule non modificabile.');
        }

        $normalized = $this->validateRecurringPayload((int) $context['household']['id'], $payload, $context['members'], $existing);
        $db = $this->db ?? Database::connect();
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();

        $ruleModel->update($ruleId, [
            'frequency' => $normalized['schedule']['frequency'],
            'interval_value' => $normalized['schedule']['interval_value'],
            'by_weekday' => $normalized['schedule']['by_weekday_json'],
            'day_of_month' => $normalized['schedule']['day_of_month'],
            'starts_at' => $normalized['schedule']['starts_at'],
            'ends_at' => $normalized['schedule']['ends_at'],
            'next_run_at' => $normalized['schedule']['next_run_at'],
            'is_active' => $normalized['schedule']['is_active'],
            'config_json' => json_encode($normalized['config'], JSON_THROW_ON_ERROR),
            'updated_by' => $actorUserId,
        ]);

        $after = $ruleModel->findExpenseRuleForHousehold((int) $context['household']['id'], $ruleId) ?? [];

        $audit->record(
            action: 'recurring_rule.updated',
            entityType: 'recurring_rule',
            entityId: $ruleId,
            actorUserId: $actorUserId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );

        $db->transComplete();

        return $this->decorateRule($after);
    }

    public function disable(int $actorUserId, string $identifier, int $ruleId): void
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            throw new DomainException('Recurring rule non disponibile.');
        }

        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel();
        $rule = $ruleModel->findExpenseRuleForHousehold((int) $context['household']['id'], $ruleId);

        if ($rule === null || ! $this->canManageRule($actorUserId, $identifier, $rule)) {
            throw new DomainException('Recurring rule non disattivabile.');
        }

        $db = $this->db ?? Database::connect();
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();

        $ruleModel->update($ruleId, [
            'is_active' => 0,
            'next_run_at' => null,
            'updated_by' => $actorUserId,
        ]);

        $after = $ruleModel->findExpenseRuleForHousehold((int) $context['household']['id'], $ruleId) ?? [];

        $audit->record(
            action: 'recurring_rule.disabled',
            entityType: 'recurring_rule',
            entityId: $ruleId,
            actorUserId: $actorUserId,
            householdId: (int) $context['household']['id'],
            before: $rule,
            after: $after,
        );

        $db->transComplete();
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array<string, mixed>> $members
     * @param array<string, mixed>|null $existingRule
     * @return array{schedule: array<string, mixed>, config: array<string, mixed>}
     */
    public function validateRecurringPayload(int $householdId, array $payload, array $members, ?array $existingRule = null): array
    {
        $frequency = strtolower(trim((string) ($payload['frequency'] ?? '')));
        $intervalValue = max(1, (int) ($payload['interval_value'] ?? 1));
        $startsAt = trim((string) ($payload['starts_at'] ?? ''));
        $endsAt = trim((string) ($payload['ends_at'] ?? ''));
        $dayOfMonth = $payload['day_of_month'] ?? null;
        $byWeekday = array_values(array_filter(array_map(static fn (mixed $value): int => (int) $value, (array) ($payload['by_weekday'] ?? [])), static fn (int $value): bool => $value >= 1 && $value <= 7));
        $customUnit = strtolower(trim((string) ($payload['custom_unit'] ?? '')));

        if (! in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true)) {
            throw new DomainException('Frequenza recurring non supportata.');
        }

        if ($startsAt === '') {
            throw new DomainException('La data di inizio recurring e obbligatoria.');
        }

        $startsAtDateTime = new DateTimeImmutable($startsAt);
        $endsAtDateTime = $endsAt === '' ? null : new DateTimeImmutable($endsAt);

        if ($endsAtDateTime !== null && $endsAtDateTime < $startsAtDateTime) {
            throw new DomainException('La data di fine recurring deve essere successiva alla data di inizio.');
        }

        if ($frequency === 'weekly' && $byWeekday === []) {
            $byWeekday = [(int) $startsAtDateTime->format('N')];
        }

        if ($frequency === 'monthly') {
            $dayOfMonth = $dayOfMonth === null || $dayOfMonth === '' ? (int) $startsAtDateTime->format('j') : (int) $dayOfMonth;

            if ($dayOfMonth < 1 || $dayOfMonth > 31) {
                throw new DomainException('Il giorno del mese deve essere compreso tra 1 e 31.');
            }
        } else {
            $dayOfMonth = null;
        }

        if ($frequency === 'custom' && ! in_array($customUnit, ['day', 'week', 'month', 'year'], true)) {
            throw new DomainException('La custom recurring rule richiede un intervallo valido.');
        }

        $expenseTemplatePayload = [
            'title' => $payload['title'] ?? null,
            'description' => $payload['description'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'total_amount' => $payload['total_amount'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'split_method' => $payload['split_method'] ?? null,
            'payers' => $payload['payers'] ?? [],
            'splits' => $payload['splits'] ?? [],
        ];

        $normalizedExpense = ($this->expenseValidationService ?? new ExpenseValidationService())
            ->validateAndNormalize($householdId, $expenseTemplatePayload, $members);

        $config = [
            'template' => [
                'title' => trim((string) $payload['title']),
                'description' => $this->nullableString($payload['description'] ?? null),
                'currency' => strtoupper((string) $payload['currency']),
                'total_amount' => $normalizedExpense['total_amount'],
                'category_id' => $this->nullableInt($payload['category_id'] ?? null),
                'split_method' => (string) $payload['split_method'],
                'payers' => $normalizedExpense['payers'],
                'splits' => $normalizedExpense['splits'],
            ],
            'schedule' => [
                'custom_unit' => $frequency === 'custom' ? $customUnit : null,
            ],
            'series' => [
                'version' => $this->seriesVersion($existingRule),
                'supports_this_occurrence' => true,
                'supports_future_occurrences' => true,
            ],
        ];

        $scheduleRule = [
            'frequency' => $frequency,
            'interval_value' => $intervalValue,
            'starts_at' => $startsAtDateTime->format('Y-m-d H:i:s'),
            'ends_at' => $endsAtDateTime?->format('Y-m-d H:i:s'),
            'day_of_month' => $dayOfMonth,
            'by_weekday' => $byWeekday,
            'config_json' => json_encode($config, JSON_THROW_ON_ERROR),
        ];

        $scheduleService = $this->recurringScheduleService ?? new RecurringScheduleService();
        $nextRunAt = $scheduleService->firstRunAt($scheduleRule);
        $isActive = 1;

        if ($existingRule !== null && ! empty($existingRule['last_run_at'])) {
            $lastRunAt = new DateTimeImmutable((string) $existingRule['last_run_at']);
            $nextAfterLast = $scheduleService->nextRunAt($scheduleRule, $lastRunAt);

            if ($nextAfterLast !== null) {
                $nextRunAt = $nextAfterLast;
            } else {
                $nextRunAt = null;
                $isActive = 0;
            }
        }

        $scheduleRule['next_run_at'] = $nextRunAt?->format('Y-m-d H:i:s');
        $scheduleRule['by_weekday_json'] = $byWeekday === [] ? null : json_encode($byWeekday, JSON_THROW_ON_ERROR);
        $scheduleRule['is_active'] = $isActive;

        return [
            'schedule' => $scheduleRule,
            'config' => $config,
        ];
    }

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    public function decorateRule(array $rule): array
    {
        $config = json_decode((string) ($rule['config_json'] ?? '{}'), true);
        $decoded = is_array($config) ? $config : [];

        $rule['template'] = $decoded['template'] ?? [];
        $rule['schedule_config'] = $decoded['schedule'] ?? [];
        $rule['series'] = $decoded['series'] ?? [];
        $rule['by_weekday_list'] = [];

        if (! empty($rule['by_weekday'])) {
            $weekdays = json_decode((string) $rule['by_weekday'], true);

            if (is_array($weekdays)) {
                $rule['by_weekday_list'] = array_values(array_map(static fn (mixed $value): int => (int) $value, $weekdays));
            }
        }

        return $rule;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $actorUserId, string $identifier): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'household' => $household,
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold((int) $household['id']),
        ];
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function canManageRule(int $actorUserId, string $identifier, array $rule): bool
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');

        if ($authorization->hasPermission($actorUserId, $identifier, Permission::EDIT_ANY_EXPENSE)) {
            return true;
        }

        return $authorization->hasPermission($actorUserId, $identifier, Permission::EDIT_OWN_EXPENSE)
            && (int) ($rule['created_by'] ?? 0) === $actorUserId;
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = is_string($value) ? trim($value) : '';

        return $resolved === '' ? null : $resolved;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed>|null $existingRule
     */
    private function seriesVersion(?array $existingRule): int
    {
        if ($existingRule === null) {
            return 1;
        }

        $config = json_decode((string) ($existingRule['config_json'] ?? '{}'), true);
        $decoded = is_array($config) ? $config : [];
        $version = (int) ($decoded['series']['version'] ?? 1);

        return max(1, $version + 1);
    }
}
