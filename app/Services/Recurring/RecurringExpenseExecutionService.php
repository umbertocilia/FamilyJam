<?php

declare(strict_types=1);

namespace App\Services\Recurring;

use App\Models\Audit\AuditLogModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Services\Audit\AuditLogService;
use App\Services\Expenses\ExpenseValidationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;

final class RecurringExpenseExecutionService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?RecurringRuleModel $recurringRuleModel = null,
        private readonly ?RecurringScheduleService $recurringScheduleService = null,
        private readonly ?ExpenseModel $expenseModel = null,
        private readonly ?ExpensePayerModel $expensePayerModel = null,
        private readonly ?ExpenseSplitModel $expenseSplitModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ExpenseValidationService $expenseValidationService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array{processed_rules:int, generated_expenses:int, skipped_duplicates:int, disabled_rules:int, anomalies:int}
     */
    public function runDue(?DateTimeImmutable $now = null, int $limit = 100): array
    {
        $db = $this->db ?? Database::connect();
        $ruleModel = $this->recurringRuleModel ?? new RecurringRuleModel($db);
        $schedule = $this->recurringScheduleService ?? new RecurringScheduleService();
        $expenseModel = $this->expenseModel ?? new ExpenseModel($db);
        $expensePayerModel = $this->expensePayerModel ?? new ExpensePayerModel($db);
        $expenseSplitModel = $this->expenseSplitModel ?? new ExpenseSplitModel($db);
        $membershipModel = $this->householdMembershipModel ?? new HouseholdMembershipModel($db);
        $validator = $this->expenseValidationService ?? new ExpenseValidationService();
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $now = $now ?? new DateTimeImmutable('now');
        $dueRules = $ruleModel->listDueExpenseRules($now->format('Y-m-d H:i:s'), $limit);

        $summary = [
            'processed_rules' => 0,
            'generated_expenses' => 0,
            'skipped_duplicates' => 0,
            'disabled_rules' => 0,
            'anomalies' => 0,
        ];

        foreach ($dueRules as $rule) {
            $summary['processed_rules']++;
            $rule = $this->decodeRule($rule);
            $safety = 0;

            while (! empty($rule['next_run_at']) && new DateTimeImmutable((string) $rule['next_run_at']) <= $now && $safety < 50) {
                $safety++;
                $occurrenceAt = new DateTimeImmutable((string) $rule['next_run_at']);
                $occurrenceDate = $occurrenceAt->format('Y-m-d');

                try {
                    $existing = $expenseModel->findByRecurringRuleAndDate((int) $rule['id'], $occurrenceDate);

                    if ($existing !== null && $existing['deleted_at'] === null) {
                        $summary['skipped_duplicates']++;
                        $audit->record(
                            action: 'recurring_rule.expense_duplicate_skipped',
                            entityType: 'recurring_rule',
                            entityId: (int) $rule['id'],
                            actorUserId: (int) $rule['created_by'],
                            householdId: (int) $rule['household_id'],
                            metadata: ['expense_id' => (int) $existing['id'], 'expense_date' => $occurrenceDate],
                        );
                    } else {
                        $members = $membershipModel->listForHousehold((int) $rule['household_id']);
                        $template = (array) ($rule['template'] ?? []);
                        $normalized = $validator->validateAndNormalize((int) $rule['household_id'], $template, $members);

                        $db->transException(true)->transStart();

                        $expenseId = $expenseModel->insert([
                            'household_id' => (int) $rule['household_id'],
                            'recurring_rule_id' => (int) $rule['id'],
                            'category_id' => $template['category_id'] ?? null,
                            'receipt_attachment_id' => null,
                            'title' => (string) $template['title'],
                            'description' => $template['description'] ?? null,
                            'expense_date' => $occurrenceDate,
                            'currency' => strtoupper((string) $template['currency']),
                            'total_amount' => $normalized['total_amount'],
                            'split_method' => (string) $template['split_method'],
                            'status' => 'active',
                            'created_by' => (int) $rule['created_by'],
                            'updated_by' => (int) $rule['created_by'],
                        ], true);

                        $expensePayerModel->replaceForExpense((int) $expenseId, $normalized['payers']);
                        $expenseSplitModel->replaceForExpense((int) $expenseId, $normalized['splits']);

                        $audit->record(
                            action: 'expense.generated',
                            entityType: 'expense',
                            entityId: (int) $expenseId,
                            actorUserId: (int) $rule['created_by'],
                            householdId: (int) $rule['household_id'],
                            metadata: ['recurring_rule_id' => (int) $rule['id'], 'expense_date' => $occurrenceDate],
                        );

                        $db->transComplete();
                        $summary['generated_expenses']++;
                    }

                    $nextRun = $schedule->nextRunAt($rule, $occurrenceAt);
                    $ruleModel->update((int) $rule['id'], [
                        'last_run_at' => $occurrenceAt->format('Y-m-d H:i:s'),
                        'next_run_at' => $nextRun?->format('Y-m-d H:i:s'),
                        'is_active' => $nextRun === null ? 0 : 1,
                    ]);

                    if ($nextRun === null) {
                        $summary['disabled_rules']++;
                    }

                    $rule['last_run_at'] = $occurrenceAt->format('Y-m-d H:i:s');
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

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    private function decodeRule(array $rule): array
    {
        $config = json_decode((string) ($rule['config_json'] ?? '{}'), true);
        $decoded = is_array($config) ? $config : [];
        $rule['template'] = $decoded['template'] ?? [];
        $rule['schedule_config'] = $decoded['schedule'] ?? [];

        return $rule;
    }
}
