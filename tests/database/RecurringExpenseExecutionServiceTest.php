<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Expenses\ExpenseValidationService;
use App\Services\Expenses\SplitCalculationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Recurring\RecurringExpenseExecutionService;
use App\Services\Recurring\RecurringExpenseService;
use App\Services\Recurring\RecurringScheduleService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class RecurringExpenseExecutionServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testExecutorGeneratesExpenseAndAdvancesRecurringRule(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-recurring@example.test', 'Owner Recurring');
        $memberId = $this->createUser($db, 'member-recurring@example.test', 'Member Recurring');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Recurring');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $rule = $this->recurringExpenseService($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Pulizie periodiche',
            'description' => 'Template recurring per costi di casa.',
            'currency' => 'EUR',
            'total_amount' => '48.00',
            'category_id' => '',
            'split_method' => 'equal',
            'frequency' => 'daily',
            'interval_value' => '1',
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '',
            'day_of_month' => '',
            'custom_unit' => '',
            'by_weekday' => [],
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '48.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $summary = $this->recurringExpenseExecutionService($db)->runDue(new DateTimeImmutable('2026-04-03 09:05:00'));

        $this->assertSame(1, $summary['processed_rules']);
        $this->assertSame(1, $summary['generated_expenses']);
        $this->assertSame(0, $summary['skipped_duplicates']);
        $this->assertSame(0, $summary['disabled_rules']);
        $this->assertSame(0, $summary['anomalies']);

        $expenses = (new ExpenseModel($db))
            ->where('household_id', (int) $household['id'])
            ->where('recurring_rule_id', (int) $rule['id'])
            ->findAll();

        $this->assertCount(1, $expenses);
        $this->assertSame('2026-04-03', $expenses[0]['expense_date']);
        $this->assertCount(1, (new ExpensePayerModel($db))->listForExpense((int) $expenses[0]['id']));
        $this->assertCount(2, (new ExpenseSplitModel($db))->listForExpense((int) $expenses[0]['id']));

        $storedRule = (new RecurringRuleModel($db))->find((int) $rule['id']);
        $this->assertNotNull($storedRule);
        $this->assertSame('2026-04-03 09:00:00', $storedRule['last_run_at']);
        $this->assertSame('2026-04-04 09:00:00', $storedRule['next_run_at']);
        $this->assertSame(1, (int) $storedRule['is_active']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('expense', (int) $expenses[0]['id'], (int) $household['id']),
        );

        $this->assertSame(['expense.generated'], $auditActions);
    }

    public function testExecutorSkipsDuplicateOccurrenceWhenExpenseAlreadyExists(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-recurring-dup@example.test', 'Owner Dup');
        $memberId = $this->createUser($db, 'member-recurring-dup@example.test', 'Member Dup');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Recurring Dup');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $rule = $this->recurringExpenseService($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Internet mensile',
            'description' => null,
            'currency' => 'EUR',
            'total_amount' => '30.00',
            'category_id' => '',
            'split_method' => 'equal',
            'frequency' => 'daily',
            'interval_value' => '1',
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '',
            'day_of_month' => '',
            'custom_unit' => '',
            'by_weekday' => [],
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '30.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        (new ExpenseModel($db))->insert([
            'household_id' => (int) $household['id'],
            'recurring_rule_id' => (int) $rule['id'],
            'category_id' => null,
            'receipt_attachment_id' => null,
            'title' => 'Internet mensile',
            'description' => null,
            'expense_date' => '2026-04-03',
            'currency' => 'EUR',
            'total_amount' => '30.00',
            'split_method' => 'equal',
            'status' => 'active',
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $summary = $this->recurringExpenseExecutionService($db)->runDue(new DateTimeImmutable('2026-04-03 09:05:00'));

        $this->assertSame(1, $summary['processed_rules']);
        $this->assertSame(0, $summary['generated_expenses']);
        $this->assertSame(1, $summary['skipped_duplicates']);
        $this->assertSame(0, $summary['anomalies']);

        $expenses = (new ExpenseModel($db))
            ->where('household_id', (int) $household['id'])
            ->where('recurring_rule_id', (int) $rule['id'])
            ->findAll();

        $this->assertCount(1, $expenses);

        $ruleAuditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('recurring_rule', (int) $rule['id'], (int) $household['id']),
        );

        $this->assertContains('recurring_rule.expense_duplicate_skipped', $ruleAuditActions);
    }

    public function testExecutorDisablesRuleAfterFinalOccurrence(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-recurring-end@example.test', 'Owner End');
        $memberId = $this->createUser($db, 'member-recurring-end@example.test', 'Member End');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Recurring End');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $rule = $this->recurringExpenseService($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Affitto una tantum',
            'description' => 'Deve essere generata una sola volta.',
            'currency' => 'EUR',
            'total_amount' => '500.00',
            'category_id' => '',
            'split_method' => 'exact',
            'frequency' => 'daily',
            'interval_value' => '1',
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '2026-04-03 09:00:00',
            'day_of_month' => '',
            'custom_unit' => '',
            'by_weekday' => [],
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '500.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1', 'owed_amount' => '250.00'],
                $memberId => ['enabled' => '1', 'owed_amount' => '250.00'],
            ],
        ]);

        $summary = $this->recurringExpenseExecutionService($db)->runDue(new DateTimeImmutable('2026-04-03 09:05:00'));

        $this->assertSame(1, $summary['generated_expenses']);
        $this->assertSame(1, $summary['disabled_rules']);
        $this->assertSame(0, $summary['anomalies']);

        $storedRule = (new RecurringRuleModel($db))->find((int) $rule['id']);
        $this->assertNotNull($storedRule);
        $this->assertSame(0, (int) $storedRule['is_active']);
        $this->assertNull($storedRule['next_run_at']);
    }

    public function testUpdatingExecutedRuleKeepsSeriesProgressionFromLastRun(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-recurring-update@example.test', 'Owner Update');
        $memberId = $this->createUser($db, 'member-recurring-update@example.test', 'Member Update');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Recurring Update');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $service = $this->recurringExpenseService($db);
        $rule = $service->create($ownerId, (string) $household['slug'], [
            'title' => 'Abbonamento streaming',
            'description' => null,
            'currency' => 'EUR',
            'total_amount' => '18.00',
            'category_id' => '',
            'split_method' => 'equal',
            'frequency' => 'daily',
            'interval_value' => '1',
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '',
            'day_of_month' => '',
            'custom_unit' => '',
            'by_weekday' => [],
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '18.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        (new RecurringRuleModel($db))->update((int) $rule['id'], [
            'last_run_at' => '2026-04-03 09:00:00',
            'next_run_at' => '2026-04-04 09:00:00',
        ]);

        $updated = $service->update($ownerId, (string) $household['slug'], (int) $rule['id'], [
            'title' => 'Abbonamento streaming premium',
            'description' => null,
            'currency' => 'EUR',
            'total_amount' => '18.00',
            'category_id' => '',
            'split_method' => 'equal',
            'frequency' => 'daily',
            'interval_value' => '2',
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '',
            'day_of_month' => '',
            'custom_unit' => '',
            'by_weekday' => [],
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '18.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $this->assertSame('2026-04-05 09:00:00', $updated['next_run_at']);
        $this->assertSame(1, (int) $updated['is_active']);
        $this->assertSame('Abbonamento streaming premium', $updated['template']['title']);
        $this->assertSame(2, (int) ($updated['series']['version'] ?? 0));
    }

    private function createUser(BaseConnection $db, string $email, string $displayName): int
    {
        return (int) (new UserModel($db))->insert([
            'email' => $email,
            'password_hash' => password_hash('SecurePass123', PASSWORD_DEFAULT),
            'display_name' => $displayName,
            'locale' => 'it',
            'theme' => 'system',
            'timezone' => 'Europe/Rome',
            'status' => 'active',
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function provisionHousehold(BaseConnection $db, int $ownerId, string $name): array
    {
        return (new HouseholdProvisioningService(
            db: $db,
            householdModel: new HouseholdModel($db),
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            roleModel: new RoleModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        ))->create($ownerId, [
            'name' => $name,
            'locale' => 'it',
        ]);
    }

    private function attachMemberRole(BaseConnection $db, int $householdId, int $userId, int $assignedBy): void
    {
        $membershipId = (new HouseholdMembershipModel($db))->insert([
            'household_id' => $householdId,
            'user_id' => $userId,
            'invited_by_user_id' => $assignedBy,
            'status' => 'active',
            'nickname' => null,
            'joined_at' => date('Y-m-d H:i:s'),
        ], true);

        $memberRole = (new RoleModel($db))->findByCode(SystemRole::MEMBER, $householdId);
        $this->assertNotNull($memberRole);

        (new MembershipRoleModel($db))->insert([
            'membership_id' => $membershipId,
            'role_id' => (int) $memberRole['id'],
            'assigned_by_user_id' => $assignedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function recurringExpenseService(BaseConnection $db): RecurringExpenseService
    {
        return new RecurringExpenseService(
            db: $db,
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            expenseValidationService: new ExpenseValidationService(new SplitCalculationService()),
            recurringScheduleService: new RecurringScheduleService(),
            recurringRuleModel: new RecurringRuleModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            householdModel: new HouseholdModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }

    private function recurringExpenseExecutionService(BaseConnection $db): RecurringExpenseExecutionService
    {
        return new RecurringExpenseExecutionService(
            db: $db,
            recurringRuleModel: new RecurringRuleModel($db),
            recurringScheduleService: new RecurringScheduleService(),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            expenseValidationService: new ExpenseValidationService(new SplitCalculationService()),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
