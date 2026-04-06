<?php

declare(strict_types=1);

namespace App\Services\Chores;

use App\Models\Audit\AuditLogModel;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Households\HouseholdModel;
use App\Services\Audit\AuditLogService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateInterval;
use DateTimeImmutable;

final class ChoreReminderService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @return array{overdue_synced:int, reminders_marked:int}
     */
    public function run(?DateTimeImmutable $now = null, int $leadHours = 24, int $limit = 100): array
    {
        $db = $this->db ?? Database::connect();
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $householdModel = $this->householdModel ?? new HouseholdModel($db);
        $notificationService = $this->notificationService ?? new NotificationService($db);
        $resolvedNow = $now ?? new DateTimeImmutable('now');
        $overdueSynced = $occurrenceModel->syncOverdueStatuses($resolvedNow->format('Y-m-d H:i:s'));
        $dueBefore = $resolvedNow->add(new DateInterval('PT' . max(1, $leadHours) . 'H'))->format('Y-m-d H:i:s');
        $rows = $occurrenceModel->listReminderCandidates($dueBefore, $limit);
        $marked = 0;
        $householdCache = [];

        foreach ($rows as $row) {
            $occurrenceModel->update((int) $row['id'], [
                'reminder_sent_at' => $resolvedNow->format('Y-m-d H:i:s'),
            ]);
            $audit->record(
                action: 'chore_occurrence.reminder_marked',
                entityType: 'chore_occurrence',
                entityId: (int) $row['id'],
                actorUserId: null,
                householdId: (int) $row['household_id'],
                metadata: [
                    'due_at' => (string) $row['due_at'],
                    'assigned_user_id' => (int) ($row['assigned_user_id'] ?? 0),
                ],
            );

            $assignedUserId = (int) ($row['assigned_user_id'] ?? 0);

            if ($assignedUserId > 0) {
                $householdId = (int) $row['household_id'];

                if (! array_key_exists($householdId, $householdCache)) {
                    $householdCache[$householdId] = $householdModel->find($householdId);
                }

                $notificationService->notifyChoreDueSoon(
                    $assignedUserId,
                    $householdId,
                    (string) ($householdCache[$householdId]['slug'] ?? ''),
                    (int) $row['id'],
                    (string) ($row['chore_title'] ?? 'Chore'),
                    (string) ($row['due_at'] ?? ''),
                );
            }

            $marked++;
        }

        return [
            'overdue_synced' => $overdueSynced,
            'reminders_marked' => $marked,
        ];
    }
}
