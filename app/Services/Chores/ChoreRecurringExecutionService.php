<?php

declare(strict_types=1);

namespace App\Services\Chores;

use App\Models\Audit\AuditLogModel;
use App\Models\Chores\ChoreModel;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\RecurringRuleModel;
use App\Services\Audit\AuditLogService;
use App\Services\Recurring\RecurringScheduleService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;

final class ChoreRecurringExecutionService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?RecurringRuleModel $recurringRuleModel = null,
        private readonly ?ChoreModel $choreModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?RecurringScheduleService $recurringScheduleService = null,
        private readonly ?ChoreOccurrenceService $choreOccurrenceService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array{processed_rules:int, generated_occurrences:int, skipped_duplicates:int, disabled_rules:int, anomalies:int}
     */
    public function runDue(?DateTimeImmutable $now = null, int $limit = 100): array
    {
        $db = $this->db ?? Database::connect();
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $choreModel = $this->choreModel ?? new ChoreModel($db);
        $occurrenceModel = $this->choreOccurrenceModel ?? new ChoreOccurrenceModel($db);
        $schedule = $this->recurringScheduleService ?? service('recurringSchedule');
        $occurrenceService = $this->choreOccurrenceService ?? service('choreOccurrenceService');
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $resolvedNow = $now ?? new DateTimeImmutable('now');
        $dueRules = $ruleModel->listDueRulesForEntity('chore', $resolvedNow->format('Y-m-d H:i:s'), $limit);

        $summary = [
            'processed_rules' => 0,
            'generated_occurrences' => 0,
            'skipped_duplicates' => 0,
            'disabled_rules' => 0,
            'anomalies' => 0,
        ];

        foreach ($dueRules as $rule) {
            $summary['processed_rules']++;
            $chore = $choreModel->findByRecurringRule((int) $rule['household_id'], (int) $rule['id']);
            $safety = 0;

            if ($chore === null) {
                $ruleModel->update((int) $rule['id'], [
                    'is_active' => 0,
                    'next_run_at' => null,
                ]);
                $audit->record(
                    action: 'recurring_rule.generation_failed',
                    entityType: 'recurring_rule',
                    entityId: (int) $rule['id'],
                    actorUserId: (int) $rule['created_by'],
                    householdId: (int) $rule['household_id'],
                    metadata: [
                        'entity_type' => 'chore',
                        'message' => 'Chore template not found for recurring rule.',
                    ],
                );
                $summary['anomalies']++;
                $summary['disabled_rules']++;

                continue;
            }

            while (! empty($rule['next_run_at']) && new DateTimeImmutable((string) $rule['next_run_at']) <= $resolvedNow && $safety < 50) {
                $safety++;
                $occurrenceAt = new DateTimeImmutable((string) $rule['next_run_at']);
                $dueAt = $occurrenceAt->format('Y-m-d H:i:s');

                try {
                    if ((int) ($chore['is_active'] ?? 0) !== 1) {
                        $ruleModel->update((int) $rule['id'], ['is_active' => 0, 'next_run_at' => null]);
                        $summary['disabled_rules']++;
                        break;
                    }

                    $existing = $occurrenceModel->findByChoreAndDueAt((int) $chore['id'], $dueAt);

                    $db->transException(true)->transStart();

                    if ($existing !== null) {
                        $summary['skipped_duplicates']++;
                        $audit->record(
                            action: 'recurring_rule.chore_duplicate_skipped',
                            entityType: 'recurring_rule',
                            entityId: (int) $rule['id'],
                            actorUserId: (int) $rule['created_by'],
                            householdId: (int) $rule['household_id'],
                            metadata: [
                                'chore_id' => (int) $chore['id'],
                                'occurrence_id' => (int) $existing['id'],
                                'due_at' => $dueAt,
                            ],
                        );
                    } else {
                        $occurrenceService->generateOccurrenceFromChore($chore, $dueAt, (int) $rule['created_by']);
                        $summary['generated_occurrences']++;
                    }

                    $nextRun = $schedule->nextRunAt($rule, $occurrenceAt);
                    $ruleModel->update((int) $rule['id'], [
                        'last_run_at' => $dueAt,
                        'next_run_at' => $nextRun?->format('Y-m-d H:i:s'),
                        'is_active' => $nextRun === null ? 0 : 1,
                    ]);
                    $db->transComplete();

                    if ($nextRun === null) {
                        $summary['disabled_rules']++;
                    }

                    $rule['last_run_at'] = $dueAt;
                    $rule['next_run_at'] = $nextRun?->format('Y-m-d H:i:s');

                    if ($nextRun === null) {
                        break;
                    }
                } catch (\Throwable $exception) {
                    $summary['anomalies']++;
                    $summary['disabled_rules']++;
                    $ruleModel->update((int) $rule['id'], [
                        'is_active' => 0,
                        'next_run_at' => null,
                    ]);
                    $audit->record(
                        action: 'recurring_rule.generation_failed',
                        entityType: 'recurring_rule',
                        entityId: (int) $rule['id'],
                        actorUserId: (int) $rule['created_by'],
                        householdId: (int) $rule['household_id'],
                        metadata: [
                            'entity_type' => 'chore',
                            'message' => $exception->getMessage(),
                            'next_run_at' => (string) ($rule['next_run_at'] ?? ''),
                        ],
                    );
                    break;
                }
            }
        }

        return $summary;
    }
}
