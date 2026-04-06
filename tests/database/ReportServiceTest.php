<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Reports\ReportService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class ReportServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testExpenseAndChoreReportsAggregateExpectedData(): void
    {
        $db = db_connect($this->DBGroup);
        $today = new DateTimeImmutable('today');
        $expenseOneDate = $today->format('Y-m-d');
        $expenseTwoDate = $today->modify('-1 month')->format('Y-m-d');
        $completedDueAt = $today->modify('-1 day')->format('Y-m-d') . ' 18:00:00';
        $skippedDueAt = $today->modify('-2 days')->format('Y-m-d') . ' 18:00:00';
        $overdueDueAt = $today->modify('-3 days')->format('Y-m-d') . ' 18:00:00';
        $ownerId = $this->createUser($db, 'owner-report@example.test', 'Owner Report');
        $memberId = $this->createUser($db, 'member-report@example.test', 'Member Report');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Report');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $groceries = (new ExpenseCategoryModel($db))->where('code', 'groceries')->first();
        $utilities = (new ExpenseCategoryModel($db))->where('code', 'utilities')->first();
        $this->assertNotNull($groceries);
        $this->assertNotNull($utilities);

        $expenseOneId = (int) (new ExpenseModel($db))->insert([
            'household_id' => (int) $household['id'],
            'category_id' => (int) $groceries['id'],
            'title' => 'Spesa supermercato',
            'description' => null,
            'expense_date' => $expenseOneDate,
            'currency' => 'EUR',
            'total_amount' => '60.00',
            'split_method' => 'equal',
            'status' => 'active',
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $expenseTwoId = (int) (new ExpenseModel($db))->insert([
            'household_id' => (int) $household['id'],
            'category_id' => (int) $utilities['id'],
            'title' => 'Bollette',
            'description' => null,
            'expense_date' => $expenseTwoDate,
            'currency' => 'EUR',
            'total_amount' => '40.00',
            'split_method' => 'exact',
            'status' => 'edited',
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        (new ExpensePayerModel($db))->insertBatch([
            ['expense_id' => $expenseOneId, 'user_id' => $ownerId, 'amount_paid' => '60.00'],
            ['expense_id' => $expenseTwoId, 'user_id' => $memberId, 'amount_paid' => '40.00'],
        ]);

        (new ExpenseSplitModel($db))->insertBatch([
            ['expense_id' => $expenseOneId, 'user_id' => $ownerId, 'owed_amount' => '30.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
            ['expense_id' => $expenseOneId, 'user_id' => $memberId, 'owed_amount' => '30.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
            ['expense_id' => $expenseTwoId, 'user_id' => $ownerId, 'owed_amount' => '20.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
            ['expense_id' => $expenseTwoId, 'user_id' => $memberId, 'owed_amount' => '20.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
        ]);

        $choreId = (int) $db->table('chores')->insert([
            'household_id' => (int) $household['id'],
            'title' => 'Bagno',
            'description' => null,
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $memberId,
            'rotation_anchor_user_id' => null,
            'points' => 5,
            'estimated_minutes' => 20,
            'is_active' => 1,
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
            'created_at' => $today->format('Y-m-d') . ' 09:00:00',
            'updated_at' => $today->format('Y-m-d') . ' 09:00:00',
        ]) ? (int) $db->insertID() : 0;

        $this->assertGreaterThan(0, $choreId);

        $db->table('chore_occurrences')->insertBatch([
            [
                'household_id' => (int) $household['id'],
                'chore_id' => $choreId,
                'assigned_user_id' => $memberId,
                'due_at' => $completedDueAt,
                'completed_at' => substr($completedDueAt, 0, 10) . ' 19:00:00',
                'completed_by' => $memberId,
                'status' => 'completed',
                'points_awarded' => 5,
                'created_at' => substr($completedDueAt, 0, 10) . ' 09:00:00',
                'updated_at' => substr($completedDueAt, 0, 10) . ' 19:00:00',
            ],
            [
                'household_id' => (int) $household['id'],
                'chore_id' => $choreId,
                'assigned_user_id' => $ownerId,
                'due_at' => $skippedDueAt,
                'skipped_at' => substr($skippedDueAt, 0, 10) . ' 18:30:00',
                'skipped_by' => $ownerId,
                'skip_reason' => 'Fuori casa',
                'status' => 'skipped',
                'points_awarded' => 0,
                'created_at' => substr($skippedDueAt, 0, 10) . ' 09:00:00',
                'updated_at' => substr($skippedDueAt, 0, 10) . ' 18:30:00',
            ],
            [
                'household_id' => (int) $household['id'],
                'chore_id' => $choreId,
                'assigned_user_id' => $memberId,
                'due_at' => $overdueDueAt,
                'status' => 'overdue',
                'points_awarded' => 0,
                'created_at' => substr($overdueDueAt, 0, 10) . ' 09:00:00',
                'updated_at' => substr($overdueDueAt, 0, 10) . ' 09:00:00',
            ],
        ]);

        $service = $this->service($db);
        $expenseReport = $service->expenseReportContext($ownerId, (string) $household['slug'], ['months' => 3]);
        $choreReport = $service->choreReportContext($ownerId, (string) $household['slug'], ['days' => 7]);

        $this->assertNotNull($expenseReport);
        $this->assertNotNull($choreReport);
        $this->assertSame(2, $expenseReport['summary']['expenses_count']);
        $this->assertSame('100.00', $expenseReport['summary']['amount_by_currency'][0]['amount']);
        $this->assertSame('Spesa supermercato', $expenseReport['recentExpenses'][0]['title']);
        $this->assertSame('groceries', strtolower((string) $groceries['code']));
        $this->assertSame('60.00', $expenseReport['byCategory']['EUR'][0]['amount']);
        $this->assertSame($ownerId, (int) $expenseReport['topSpenders']['EUR'][0]['user_id']);
        $this->assertSame('60.00', $expenseReport['topSpenders']['EUR'][0]['paid_amount']);

        $this->assertSame(3, $choreReport['summary']['occurrences_count']);
        $this->assertSame(1, $choreReport['summary']['completed']);
        $this->assertSame(1, $choreReport['summary']['skipped']);
        $this->assertSame(1, $choreReport['summary']['overdue']);
        $this->assertSame(5, $choreReport['summary']['points_total']);
        $this->assertSame($memberId, (int) $choreReport['byUser'][0]['user_id']);
        $this->assertSame(5, (int) $choreReport['byUser'][0]['points_total']);
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
            'base_currency' => 'EUR',
            'timezone' => 'Europe/Rome',
        ]);
    }

    private function attachMemberRole(BaseConnection $db, int $householdId, int $userId, int $assignedBy): void
    {
        $membershipId = (int) (new HouseholdMembershipModel($db))->insert([
            'household_id' => $householdId,
            'user_id' => $userId,
            'invited_by_user_id' => $assignedBy,
            'status' => 'active',
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

    private function service(BaseConnection $db): ReportService
    {
        return new ReportService(
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            expenseModel: new ExpenseModel($db),
            choreOccurrenceModel: new \App\Models\Chores\ChoreOccurrenceModel($db),
            db: $db,
        );
    }
}
