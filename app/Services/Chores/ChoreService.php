<?php

declare(strict_types=1);

namespace App\Services\Chores;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Chores\ChoreModel;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Recurring\RecurringScheduleService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use DomainException;

final class ChoreService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ChoreModel $choreModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?RecurringRuleModel $recurringRuleModel = null,
        private readonly ?RecurringScheduleService $recurringScheduleService = null,
        private readonly ?ChoreOccurrenceService $choreOccurrenceService = null,
        private readonly ?ChoreFairnessService $choreFairnessService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function overviewContext(int $userId, string $identifier): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        ($this->choreOccurrenceService ?? service('choreOccurrenceService'))
            ->syncOverdueStatuses((int) $context['household']['id']);

        $choreModel = $this->choreModel ?? new ChoreModel();
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel();
        $templates = $choreModel->listForHousehold((int) $context['household']['id']);
        $allOccurrences = $occurrenceModel->listForHousehold((int) $context['household']['id']);
        $upcoming = $occurrenceModel->listAgenda(
            (int) $context['household']['id'],
            (new DateTimeImmutable('today'))->format('Y-m-d 00:00:00'),
            (new DateTimeImmutable('today +14 days'))->format('Y-m-d 23:59:59'),
        );

        $now = new DateTimeImmutable('now');
        $weekStart = new DateTimeImmutable('monday this week 00:00:00');
        $summary = [
            'active_templates' => 0,
            'due_today' => 0,
            'overdue' => 0,
            'my_open' => 0,
            'completed_this_week' => 0,
        ];

        foreach ($templates as $template) {
            if ((int) ($template['is_active'] ?? 0) === 1) {
                $summary['active_templates']++;
            }
        }

        foreach ($allOccurrences as $row) {
            $status = (string) $row['status'];
            $dueAt = new DateTimeImmutable((string) $row['due_at']);

            if ($status === 'overdue') {
                $summary['overdue']++;
            }

            if (in_array($status, ['pending', 'overdue'], true) && $dueAt->format('Y-m-d') === $now->format('Y-m-d')) {
                $summary['due_today']++;
            }

            if (in_array($status, ['pending', 'overdue'], true) && (int) ($row['assigned_user_id'] ?? 0) === $userId) {
                $summary['my_open']++;
            }

            if ($status === 'completed' && ! empty($row['completed_at']) && new DateTimeImmutable((string) $row['completed_at']) >= $weekStart) {
                $summary['completed_this_week']++;
            }
        }

        return array_merge($context, [
            'templates' => array_map(fn (array $row): array => $this->decorateChore($row), $templates),
            'upcomingOccurrences' => array_slice($upcoming, 0, 8),
            'fairness' => ($this->choreFairnessService ?? service('choreFairnessService'))->dashboardContext($userId, $identifier),
            'summary' => $summary,
            'canManageChores' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($userId, $identifier, Permission::MANAGE_CHORES),
        ]);
    }

    /**
     * @param array{assignment_mode?: string|null, is_active?: string|int|null} $filters
     * @return array<string, mixed>|null
     */
    public function templatesContext(int $userId, string $identifier, array $filters = []): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        return array_merge($context, [
            'filters' => $filters,
            'templates' => array_map(
                fn (array $row): array => $this->decorateChore($row),
                ($this->choreModel ?? new ChoreModel())->listForHousehold((int) $context['household']['id'], $filters),
            ),
            'canManageChores' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($userId, $identifier, Permission::MANAGE_CHORES),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formContext(int $userId, string $identifier, ?int $choreId = null): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            return null;
        }

        $chore = null;
        $recentOccurrences = [];

        if ($choreId !== null) {
            $chore = ($this->choreModel ?? new ChoreModel())
                ->findDetailForHousehold((int) $context['household']['id'], $choreId);

            if ($chore === null) {
                return null;
            }

            $chore = $this->decorateChore($chore);
            $recentOccurrences = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
                ->listForChore((int) $context['household']['id'], $choreId, 12);
        }

        return array_merge($context, [
            'chore' => $chore,
            'recentOccurrences' => $recentOccurrences,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $userId, string $identifier, array $payload): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            throw new DomainException('Non hai i permessi necessari per creare una faccenda.');
        }

        $normalized = $this->validatePayload($payload, $context['members']);
        $db = $this->db ?? Database::connect();
        $choreModel = $this->choreModel ?? new ChoreModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $recurringRuleId = $this->persistRecurringRule($userId, (int) $context['household']['id'], $normalized['recurring'], null);
        $choreId = $choreModel->insert([
            'household_id' => (int) $context['household']['id'],
            'recurring_rule_id' => $recurringRuleId,
            'title' => $normalized['title'],
            'description' => $normalized['description'],
            'assignment_mode' => $normalized['assignment_mode'],
            'fixed_assignee_user_id' => $normalized['fixed_assignee_user_id'],
            'rotation_anchor_user_id' => $normalized['rotation_anchor_user_id'],
            'points' => $normalized['points'],
            'estimated_minutes' => $normalized['estimated_minutes'],
            'is_active' => $normalized['is_active'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ], true);
        $after = $choreModel->findDetailForHousehold((int) $context['household']['id'], (int) $choreId) ?? [];

        if ($normalized['first_due_at'] !== null) {
            ($this->choreOccurrenceService ?? service('choreOccurrenceService'))
                ->generateOccurrenceFromChore($after, $normalized['first_due_at'], $userId);
        }

        $after = $choreModel->findDetailForHousehold((int) $context['household']['id'], (int) $choreId) ?? [];
        $audit->record(
            action: 'chore.created',
            entityType: 'chore',
            entityId: (int) $choreId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            after: $after,
        );
        $db->transComplete();

        return $this->decorateChore($after);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $userId, string $identifier, int $choreId, array $payload): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            throw new DomainException('Non hai i permessi necessari per modificare questa faccenda.');
        }

        $existing = ($this->choreModel ?? new ChoreModel())
            ->findDetailForHousehold((int) $context['household']['id'], $choreId);

        if ($existing === null) {
            throw new DomainException('Template chore non trovato.');
        }

        $normalized = $this->validatePayload($payload, $context['members'], $existing);
        $db = $this->db ?? Database::connect();
        $choreModel = $this->choreModel ?? new ChoreModel($db);
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $recurringRuleId = $this->persistRecurringRule(
            $userId,
            (int) $context['household']['id'],
            $normalized['recurring'],
            empty($existing['recurring_rule_id']) ? null : (int) $existing['recurring_rule_id'],
        );

        if ($normalized['recurring'] === null && ! empty($existing['recurring_rule_id'])) {
            $ruleModel->update((int) $existing['recurring_rule_id'], [
                'is_active' => 0,
                'next_run_at' => null,
                'updated_by' => $userId,
            ]);
            $recurringRuleId = null;
        }

        $choreModel->update($choreId, [
            'recurring_rule_id' => $recurringRuleId,
            'title' => $normalized['title'],
            'description' => $normalized['description'],
            'assignment_mode' => $normalized['assignment_mode'],
            'fixed_assignee_user_id' => $normalized['fixed_assignee_user_id'],
            'rotation_anchor_user_id' => $normalized['rotation_anchor_user_id'],
            'points' => $normalized['points'],
            'estimated_minutes' => $normalized['estimated_minutes'],
            'is_active' => $normalized['is_active'],
            'updated_by' => $userId,
        ]);

        $after = $choreModel->findDetailForHousehold((int) $context['household']['id'], $choreId) ?? [];

        if ($normalized['first_due_at'] !== null) {
            ($this->choreOccurrenceService ?? service('choreOccurrenceService'))
                ->generateOccurrenceFromChore($after, $normalized['first_due_at'], $userId);
        }

        $after = $choreModel->findDetailForHousehold((int) $context['household']['id'], $choreId) ?? [];
        $audit->record(
            action: 'chore.updated',
            entityType: 'chore',
            entityId: $choreId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();

        return $this->decorateChore($after);
    }

    /**
     * @return array<string, mixed>
     */
    public function toggleActive(int $userId, string $identifier, int $choreId): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            throw new DomainException('Non hai i permessi necessari per aggiornare questa faccenda.');
        }

        $choreModel = $this->choreModel ?? new ChoreModel();
        $existing = $choreModel->findDetailForHousehold((int) $context['household']['id'], $choreId);

        if ($existing === null) {
            throw new DomainException('Template chore non trovato.');
        }

        $db = $this->db ?? Database::connect();
        $nextActive = (int) ($existing['is_active'] ?? 0) === 1 ? 0 : 1;
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);

        $db->transException(true)->transStart();
        ($this->choreModel ?? new ChoreModel($db))->update($choreId, [
            'is_active' => $nextActive,
            'updated_by' => $userId,
        ]);

        if (! empty($existing['recurring_rule_id']) && $nextActive === 0) {
            $ruleModel->update((int) $existing['recurring_rule_id'], [
                'is_active' => 0,
                'next_run_at' => null,
                'updated_by' => $userId,
            ]);
        } elseif (! empty($existing['recurring_rule_id']) && $nextActive === 1) {
            $rule = $ruleModel->findRuleForHousehold((int) $context['household']['id'], 'chore', (int) $existing['recurring_rule_id']);

            if ($rule !== null) {
                $nextRunAt = ! empty($rule['last_run_at'])
                    ? ($this->recurringScheduleService ?? service('recurringSchedule'))->nextRunAt($rule, new DateTimeImmutable((string) $rule['last_run_at']))
                    : ($this->recurringScheduleService ?? service('recurringSchedule'))->firstRunAt($rule);

                $ruleModel->update((int) $existing['recurring_rule_id'], [
                    'is_active' => $nextRunAt === null ? 0 : 1,
                    'next_run_at' => $nextRunAt?->format('Y-m-d H:i:s'),
                    'updated_by' => $userId,
                ]);
            }
        }

        $after = ($this->choreModel ?? new ChoreModel($db))
            ->findDetailForHousehold((int) $context['household']['id'], $choreId) ?? [];
        $audit->record(
            action: $nextActive === 1 ? 'chore.activated' : 'chore.disabled',
            entityType: 'chore',
            entityId: $choreId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();

        return $this->decorateChore($after);
    }

    /**
     * @param list<array<string, mixed>> $members
     * @param array<string, mixed>|null $existing
     * @return array<string, mixed>
     */
    public function validatePayload(array $payload, array $members, ?array $existing = null): array
    {
        $activeUserIds = array_map(static fn (array $member): int => (int) $member['user_id'], $members);
        $title = trim((string) ($payload['title'] ?? ''));
        $description = $this->nullableString($payload['description'] ?? null);
        $assignmentMode = strtolower(trim((string) ($payload['assignment_mode'] ?? 'fixed')));
        $points = max(0, (int) ($payload['points'] ?? 0));
        $estimatedMinutes = max(0, (int) ($payload['estimated_minutes'] ?? 0));
        $isActive = $this->isTruthy($payload['is_active'] ?? null) ? 1 : 0;
        $fixedAssigneeUserId = $this->nullableInt($payload['fixed_assignee_user_id'] ?? null);
        $rotationAnchorUserId = $this->nullableInt($payload['rotation_anchor_user_id'] ?? null);
        $firstDueAt = $this->nullableDateTimeString($payload['first_due_at'] ?? null);

        if ($title === '' || strlen($title) < 3 || strlen($title) > 160) {
            throw new DomainException('Il titolo della faccenda deve avere tra 3 e 160 caratteri.');
        }

        if (! in_array($assignmentMode, ['fixed', 'rotation'], true)) {
            throw new DomainException('La modalita di assegnazione deve essere fixed o rotation.');
        }

        if ($fixedAssigneeUserId !== null && ! in_array($fixedAssigneeUserId, $activeUserIds, true)) {
            throw new DomainException('L assegnatario fisso deve essere un membro attivo della household.');
        }

        if ($rotationAnchorUserId !== null && ! in_array($rotationAnchorUserId, $activeUserIds, true)) {
            throw new DomainException('Il rotation anchor deve essere un membro attivo della household.');
        }

        return [
            'title' => $title,
            'description' => $description,
            'assignment_mode' => $assignmentMode,
            'fixed_assignee_user_id' => $assignmentMode === 'fixed' ? $fixedAssigneeUserId : null,
            'rotation_anchor_user_id' => $assignmentMode === 'rotation' ? $rotationAnchorUserId : null,
            'points' => $points,
            'estimated_minutes' => $estimatedMinutes,
            'is_active' => $isActive,
            'first_due_at' => $firstDueAt,
            'recurring' => $this->normalizeRecurringPayload($payload, $existing),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $userId, string $identifier): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

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
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())
                ->listActiveMembersForAssignment((int) $household['id']),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decorateChore(array $row): array
    {
        $row['recurring'] = null;

        if (! empty($row['recurring_rule_id'])) {
            $config = json_decode((string) ($row['recurring_config_json'] ?? '{}'), true);
            $decoded = is_array($config) ? $config : [];
            $row['recurring'] = [
                'frequency' => $row['recurring_frequency'] ?? null,
                'interval_value' => $row['recurring_interval_value'] ?? null,
                'by_weekday' => ! empty($row['recurring_by_weekday']) ? (json_decode((string) $row['recurring_by_weekday'], true) ?: []) : [],
                'day_of_month' => $row['recurring_day_of_month'] ?? null,
                'starts_at' => $row['recurring_starts_at'] ?? null,
                'ends_at' => $row['recurring_ends_at'] ?? null,
                'next_run_at' => $row['recurring_next_run_at'] ?? null,
                'is_active' => $row['recurring_is_active'] ?? null,
                'custom_unit' => $decoded['schedule']['custom_unit'] ?? null,
            ];
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $existing
     * @return array<string, mixed>|null
     */
    private function normalizeRecurringPayload(array $payload, ?array $existing): ?array
    {
        if (! $this->isTruthy($payload['recurring_enabled'] ?? null)) {
            return null;
        }

        $frequency = strtolower(trim((string) ($payload['frequency'] ?? '')));
        $intervalValue = max(1, (int) ($payload['interval_value'] ?? 1));
        $startsAt = trim((string) ($payload['starts_at'] ?? ''));
        $endsAt = trim((string) ($payload['ends_at'] ?? ''));
        $dayOfMonth = $payload['day_of_month'] ?? null;
        $byWeekday = array_values(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, (array) ($payload['by_weekday'] ?? [])),
            static fn (int $value): bool => $value >= 1 && $value <= 7,
        ));
        $customUnit = strtolower(trim((string) ($payload['custom_unit'] ?? '')));

        if (! in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true)) {
            throw new DomainException('Frequenza recurring non supportata.');
        }

        if ($startsAt === '') {
            throw new DomainException('La data di inizio della recurring rule e obbligatoria.');
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

        $config = [
            'schedule' => [
                'custom_unit' => $frequency === 'custom' ? $customUnit : null,
            ],
            'series' => [
                'version' => $this->seriesVersion($existing),
                'supports_this_occurrence' => true,
                'supports_future_occurrences' => true,
            ],
        ];

        $rule = [
            'frequency' => $frequency,
            'interval_value' => $intervalValue,
            'starts_at' => $startsAtDateTime->format('Y-m-d H:i:s'),
            'ends_at' => $endsAtDateTime?->format('Y-m-d H:i:s'),
            'day_of_month' => $dayOfMonth,
            'by_weekday' => $byWeekday,
            'config_json' => json_encode($config, JSON_THROW_ON_ERROR),
        ];

        $nextRunAt = ($this->recurringScheduleService ?? service('recurringSchedule'))->firstRunAt($rule);
        $isActive = 1;

        if ($existing !== null && ! empty($existing['recurring_last_run_at'])) {
            $nextAfterLast = ($this->recurringScheduleService ?? service('recurringSchedule'))
                ->nextRunAt($rule, new DateTimeImmutable((string) $existing['recurring_last_run_at']));

            if ($nextAfterLast !== null) {
                $nextRunAt = $nextAfterLast;
            } else {
                $nextRunAt = null;
                $isActive = 0;
            }
        }

        $rule['next_run_at'] = $nextRunAt?->format('Y-m-d H:i:s');
        $rule['by_weekday_json'] = $byWeekday === [] ? null : json_encode($byWeekday, JSON_THROW_ON_ERROR);
        $rule['is_active'] = $isActive;
        $rule['config_array'] = $config;

        return $rule;
    }

    private function persistRecurringRule(int $userId, int $householdId, ?array $recurring, ?int $existingRuleId): ?int
    {
        if ($recurring === null) {
            return $existingRuleId;
        }

        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($this->db ?? Database::connect());

        if ($existingRuleId === null) {
            return (int) $ruleModel->insert([
                'household_id' => $householdId,
                'entity_type' => 'chore',
                'frequency' => $recurring['frequency'],
                'interval_value' => $recurring['interval_value'],
                'by_weekday' => $recurring['by_weekday_json'],
                'day_of_month' => $recurring['day_of_month'],
                'starts_at' => $recurring['starts_at'],
                'ends_at' => $recurring['ends_at'],
                'next_run_at' => $recurring['next_run_at'],
                'last_run_at' => null,
                'is_active' => $recurring['is_active'],
                'config_json' => json_encode($recurring['config_array'], JSON_THROW_ON_ERROR),
                'created_by' => $userId,
                'updated_by' => $userId,
            ], true);
        }

        $ruleModel->update($existingRuleId, [
            'frequency' => $recurring['frequency'],
            'interval_value' => $recurring['interval_value'],
            'by_weekday' => $recurring['by_weekday_json'],
            'day_of_month' => $recurring['day_of_month'],
            'starts_at' => $recurring['starts_at'],
            'ends_at' => $recurring['ends_at'],
            'next_run_at' => $recurring['next_run_at'],
            'is_active' => $recurring['is_active'],
            'config_json' => json_encode($recurring['config_array'], JSON_THROW_ON_ERROR),
            'updated_by' => $userId,
        ]);

        return $existingRuleId;
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

    private function nullableDateTimeString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $existing
     */
    private function seriesVersion(?array $existing): int
    {
        if ($existing === null || empty($existing['recurring_config_json'])) {
            return 1;
        }

        $config = json_decode((string) $existing['recurring_config_json'], true);
        $decoded = is_array($config) ? $config : [];

        return max(1, (int) ($decoded['series']['version'] ?? 1) + 1);
    }
}
