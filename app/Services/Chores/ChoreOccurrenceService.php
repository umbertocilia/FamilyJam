<?php

declare(strict_types=1);

namespace App\Services\Chores;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Chores\ChoreModel;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateInterval;
use DateTimeImmutable;
use DomainException;

final class ChoreOccurrenceService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ChoreModel $choreModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?ChoreRotationService $choreRotationService = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @param array{status?: string|null, assigned_user_id?: string|int|null} $filters
     * @return array<string, mixed>|null
     */
    public function occurrencesContext(int $userId, string $identifier, array $filters = []): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $this->syncOverdueStatuses((int) $context['household']['id']);

        return array_merge($context, [
            'filters' => $filters,
            'occurrences' => ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
                ->listForHousehold((int) $context['household']['id'], $filters),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function myContext(int $userId, string $identifier): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $this->syncOverdueStatuses((int) $context['household']['id']);

        return array_merge($context, [
            'occurrences' => ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
                ->listForUser((int) $context['household']['id'], $userId),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function calendarContext(int $userId, string $identifier, ?string $startDate = null): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $this->syncOverdueStatuses((int) $context['household']['id']);

        try {
            $start = $startDate === null || trim($startDate) === ''
                ? new DateTimeImmutable('today')
                : new DateTimeImmutable($startDate . ' 00:00:00');
        } catch (\Throwable) {
            $start = new DateTimeImmutable('today');
        }
        $end = $start->add(new DateInterval('P13D'))->setTime(23, 59, 59);
        $rows = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->listAgenda((int) $context['household']['id'], $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));

        return array_merge($context, [
            'calendarStart' => $start,
            'calendarEnd' => $end,
            'agendaRows' => $rows,
        ]);
    }

    /**
     * @param array<string, mixed> $chore
     * @return array<string, mixed>
     */
    public function generateOccurrenceFromChore(array $chore, string $dueAt, ?int $actorUserId = null): array
    {
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel();
        $existing = $occurrenceModel->findByChoreAndDueAt((int) $chore['id'], $dueAt);

        if ($existing !== null) {
            return $existing;
        }

        $activeMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment((int) $chore['household_id']);
        $assignedUserId = $this->resolveAssignedUserId($chore, $activeMembers);
        $occurrenceId = $occurrenceModel->insert([
            'household_id' => (int) $chore['household_id'],
            'chore_id' => (int) $chore['id'],
            'assigned_user_id' => $assignedUserId,
            'due_at' => $dueAt,
            'completed_at' => null,
            'completed_by' => null,
            'skipped_at' => null,
            'skipped_by' => null,
            'skip_reason' => null,
            'status' => 'pending',
            'points_awarded' => 0,
            'reminder_sent_at' => null,
        ], true);

        ($this->auditLogService ?? new AuditLogService(new AuditLogModel($this->db ?? Database::connect())))->record(
            action: 'chore_occurrence.generated',
            entityType: 'chore_occurrence',
            entityId: (int) $occurrenceId,
            actorUserId: $actorUserId ?? (int) ($chore['updated_by'] ?? $chore['created_by'] ?? 0),
            householdId: (int) $chore['household_id'],
            metadata: [
                'chore_id' => (int) $chore['id'],
                'assigned_user_id' => $assignedUserId,
                'due_at' => $dueAt,
            ],
        );

        if ($assignedUserId !== null) {
            $household = ($this->householdModel ?? new HouseholdModel())->find((int) $chore['household_id']);

            ($this->notificationService ?? new NotificationService($this->db ?? Database::connect()))
                ->notifyChoreAssigned(
                    $assignedUserId,
                    (int) $chore['household_id'],
                    (string) ($household['slug'] ?? ''),
                    (int) $occurrenceId,
                    (string) ($chore['title'] ?? 'Chore'),
                    $dueAt,
                    $actorUserId,
                );
        }

        return $occurrenceModel->findDetailForHousehold((int) $chore['household_id'], (int) $occurrenceId) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function createOccurrenceForChore(int $userId, string $identifier, int $choreId, string $dueAt): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! ($this->householdAuthorizationService ?? service('householdAuthorization'))->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            throw new DomainException('Non hai i permessi necessari per generare un occorrenza chore.');
        }

        $chore = ($this->choreModel ?? new ChoreModel())
            ->findDetailForHousehold((int) $context['household']['id'], $choreId);

        if ($chore === null) {
            throw new DomainException('Template chore non trovato.');
        }

        if ((int) ($chore['is_active'] ?? 0) !== 1) {
            throw new DomainException('Il template chore deve essere attivo per generare nuove occorrenze.');
        }

        $resolvedDueAt = $this->normalizeDueAt($dueAt);
        $existing = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->findByChoreAndDueAt($choreId, $resolvedDueAt);

        if ($existing !== null) {
            throw new DomainException('Esiste gia una occorrenza per questa data e ora.');
        }

        $db = $this->db ?? Database::connect();
        $db->transException(true)->transStart();
        $occurrence = $this->generateOccurrenceFromChore($chore, $resolvedDueAt, $userId);
        $db->transComplete();

        return $occurrence;
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(int $userId, string $identifier, int $occurrenceId): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            throw new DomainException('Occorrenza chore non disponibile.');
        }

        $this->syncOverdueStatuses((int) $context['household']['id']);
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel();
        $occurrence = $occurrenceModel->findDetailForHousehold((int) $context['household']['id'], $occurrenceId);

        if ($occurrence === null || ! $this->canActOnOccurrence($userId, $identifier, $occurrence)) {
            throw new DomainException('Non puoi completare questa faccenda.');
        }

        if (! in_array((string) $occurrence['status'], ['pending', 'overdue'], true)) {
            throw new DomainException('Solo le faccende aperte possono essere completate.');
        }

        $now = new DateTimeImmutable('now');
        $pointsAwarded = ! empty($context['household']['chore_scoring_enabled'])
            ? (int) ($occurrence['chore_points'] ?? 0)
            : 0;
        $db = $this->db ?? Database::connect();
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $occurrenceModel->update($occurrenceId, [
            'status' => 'completed',
            'completed_at' => $now->format('Y-m-d H:i:s'),
            'completed_by' => $userId,
            'points_awarded' => $pointsAwarded,
        ]);
        $after = $occurrenceModel->findDetailForHousehold((int) $context['household']['id'], $occurrenceId) ?? [];
        $audit->record(
            action: 'chore_occurrence.completed',
            entityType: 'chore_occurrence',
            entityId: $occurrenceId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $occurrence,
            after: $after,
        );
        $householdSlug = (string) ($context['household']['slug'] ?? '');
        $recipientIds = array_values(array_unique(array_map(
            static fn (array $member): int => (int) $member['user_id'],
            ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment((int) $context['household']['id']),
        )));
        $memberDirectory = [];
        foreach ($context['members'] as $member) {
            $memberDirectory[(int) $member['user_id']] = $member;
        }

        ($this->notificationService ?? new NotificationService($db))->notifyChoreCompleted(
            $recipientIds,
            (int) $context['household']['id'],
            $householdSlug,
            $occurrenceId,
            (string) ($after['chore_title'] ?? $occurrence['chore_title'] ?? 'Chore'),
            (string) ($memberDirectory[$userId]['display_name'] ?? $memberDirectory[$userId]['email'] ?? ('User #' . $userId)),
            $userId,
        );
        $db->transComplete();

        return $after;
    }

    /**
     * @return array<string, mixed>
     */
    public function skip(int $userId, string $identifier, int $occurrenceId, string $reason): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            throw new DomainException('Occorrenza chore non disponibile.');
        }

        $this->syncOverdueStatuses((int) $context['household']['id']);
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel();
        $occurrence = $occurrenceModel->findDetailForHousehold((int) $context['household']['id'], $occurrenceId);

        if ($occurrence === null || ! $this->canActOnOccurrence($userId, $identifier, $occurrence)) {
            throw new DomainException('Non puoi saltare questa faccenda.');
        }

        if (! in_array((string) $occurrence['status'], ['pending', 'overdue'], true)) {
            throw new DomainException('Solo le faccende aperte possono essere saltate.');
        }

        $resolvedReason = trim($reason);

        if ($resolvedReason === '') {
            throw new DomainException('Specifica una motivazione per lo skip.');
        }

        $now = new DateTimeImmutable('now');
        $db = $this->db ?? Database::connect();
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $occurrenceModel->update($occurrenceId, [
            'status' => 'skipped',
            'skipped_at' => $now->format('Y-m-d H:i:s'),
            'skipped_by' => $userId,
            'skip_reason' => substr($resolvedReason, 0, 255),
            'points_awarded' => 0,
        ]);
        $after = $occurrenceModel->findDetailForHousehold((int) $context['household']['id'], $occurrenceId) ?? [];
        $audit->record(
            action: 'chore_occurrence.skipped',
            entityType: 'chore_occurrence',
            entityId: $occurrenceId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $occurrence,
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    public function syncOverdueStatuses(int $householdId, ?DateTimeImmutable $now = null): int
    {
        return ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->syncOverdueStatuses(($now ?? new DateTimeImmutable('now'))->format('Y-m-d H:i:s'), $householdId);
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
            'currentUserId' => $userId,
            'canManageChores' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($userId, $identifier, Permission::MANAGE_CHORES),
            'membership' => $membership,
            'household' => $household,
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())
                ->listActiveMembersForAssignment((int) $household['id']),
        ];
    }

    /**
     * @param array<string, mixed> $chore
     * @param list<array<string, mixed>> $activeMembers
     */
    private function resolveAssignedUserId(array $chore, array $activeMembers): ?int
    {
        if ((string) ($chore['assignment_mode'] ?? 'fixed') === 'fixed') {
            $fixedAssignee = (int) ($chore['fixed_assignee_user_id'] ?? 0);

            if ($fixedAssignee === 0) {
                return null;
            }

            foreach ($activeMembers as $member) {
                if ((int) $member['user_id'] === $fixedAssignee) {
                    return $fixedAssignee;
                }
            }

            return null;
        }

        $recent = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->listRecentAssignedForChore((int) $chore['id'], 100);
        $lastAssignedUserId = null;

        foreach ($recent as $row) {
            $candidate = (int) ($row['assigned_user_id'] ?? 0);

            if ($candidate === 0) {
                continue;
            }

            foreach ($activeMembers as $member) {
                if ((int) $member['user_id'] === $candidate) {
                    $lastAssignedUserId = $candidate;
                    break 2;
                }
            }
        }

        return ($this->choreRotationService ?? new ChoreRotationService())
            ->nextAssigneeUserId($activeMembers, empty($chore['rotation_anchor_user_id']) ? null : (int) $chore['rotation_anchor_user_id'], $lastAssignedUserId);
    }

    /**
     * @param array<string, mixed> $occurrence
     */
    private function canActOnOccurrence(int $userId, string $identifier, array $occurrence): bool
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');

        if ($authorization->hasPermission($userId, $identifier, Permission::MANAGE_CHORES)) {
            return true;
        }

        return $authorization->hasPermission($userId, $identifier, Permission::COMPLETE_CHORE)
            && (int) ($occurrence['assigned_user_id'] ?? 0) === $userId;
    }

    private function normalizeDueAt(string $dueAt): string
    {
        $resolved = trim($dueAt);

        if ($resolved === '') {
            throw new DomainException('La data di scadenza e obbligatoria.');
        }

        return (new DateTimeImmutable($resolved))->format('Y-m-d H:i:s');
    }
}
