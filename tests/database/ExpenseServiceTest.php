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
use App\Services\Expenses\ExpenseService;
use App\Services\Expenses\ExpenseValidationService;
use App\Services\Expenses\SplitCalculationService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DomainException;

/**
 * @internal
 */
final class ExpenseServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreateUpdateAndDeleteExpensePersistRowsAndAuditLogs(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-expense@example.test', 'Owner Expense');
        $memberId = $this->createUser($db, 'member-expense@example.test', 'Member Expense');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Expenses');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);
        $service = $this->expenseService($db);
        $category = (new ExpenseCategoryModel($db))->where('code', 'groceries')->first();

        $this->assertNotNull($category);

        $created = $service->create($ownerId, (string) $household['slug'], [
            'title' => 'Supermercato del weekend',
            'description' => 'Spesa condivisa per la settimana.',
            'expense_date' => '2026-04-01',
            'currency' => 'EUR',
            'total_amount' => '42.50',
            'category_id' => (string) $category['id'],
            'split_method' => 'equal',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '42.50'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $this->assertSame('active', $created['status']);
        $this->assertSame('42.50', $created['total_amount']);
        $this->assertCount(1, (new ExpensePayerModel($db))->listForExpense((int) $created['id']));
        $this->assertCount(2, (new ExpenseSplitModel($db))->listForExpense((int) $created['id']));

        $updated = $service->update($ownerId, (string) $household['slug'], (int) $created['id'], [
            'title' => 'Supermercato e pulizie',
            'description' => 'Aggiunti detergenti.',
            'expense_date' => '2026-04-02',
            'currency' => 'EUR',
            'total_amount' => '45.00',
            'category_id' => (string) $category['id'],
            'split_method' => 'percentage',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '30.00'],
                $memberId => ['enabled' => '1', 'amount' => '15.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1', 'percentage' => '50.00'],
                $memberId => ['enabled' => '1', 'percentage' => '50.00'],
            ],
        ]);

        $this->assertSame('edited', $updated['status']);
        $this->assertSame('45.00', $updated['total_amount']);

        $service->softDelete($ownerId, (string) $household['slug'], (int) $created['id']);

        $deleted = (new ExpenseModel($db))->withDeleted()->find((int) $created['id']);
        $this->assertNotNull($deleted);
        $this->assertSame('deleted', $deleted['status']);
        $this->assertNotNull($deleted['deleted_at']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('expense', (int) $created['id'], (int) $household['id']),
        );

        $this->assertSame(['expense.deleted', 'expense.updated', 'expense.created'], $auditActions);

        $auditRows = (new AuditLogModel($db))->listForEntity('expense', (int) $created['id'], (int) $household['id']);
        $this->assertNotNull($auditRows[0]['before_json']);
        $this->assertNotNull($auditRows[0]['after_json']);
        $this->assertNotNull($auditRows[1]['before_json']);
        $this->assertNotNull($auditRows[1]['after_json']);
        $this->assertNull($auditRows[2]['before_json']);
        $this->assertNotNull($auditRows[2]['after_json']);
    }

    public function testMemberCannotEditAnotherUsersExpenseWithOnlyOwnPermission(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-expense-2@example.test', 'Owner Two');
        $memberId = $this->createUser($db, 'member-expense-2@example.test', 'Member Two');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Limits');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);
        $service = $this->expenseService($db);

        $created = $service->create($ownerId, (string) $household['slug'], [
            'title' => 'Internet di casa',
            'description' => null,
            'expense_date' => '2026-04-01',
            'currency' => 'EUR',
            'total_amount' => '25.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '25.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $this->expectException(DomainException::class);

        $service->update($memberId, (string) $household['slug'], (int) $created['id'], [
            'title' => 'Internet modificato',
            'description' => null,
            'expense_date' => '2026-04-01',
            'currency' => 'EUR',
            'total_amount' => '25.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '25.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);
    }

    public function testUserFromAnotherHouseholdCannotReadExpenseContextAcrossTenants(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerAId = $this->createUser($db, 'owner-a@example.test', 'Owner A');
        $ownerBId = $this->createUser($db, 'owner-b@example.test', 'Owner B');
        $householdA = $this->provisionHousehold($db, $ownerAId, 'Casa A');
        $householdB = $this->provisionHousehold($db, $ownerBId, 'Casa B');
        $service = $this->expenseService($db);

        $created = $service->create($ownerAId, (string) $householdA['slug'], [
            'title' => 'Expense A',
            'description' => null,
            'expense_date' => '2026-04-01',
            'currency' => 'EUR',
            'total_amount' => '12.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $ownerAId => ['enabled' => '1', 'amount' => '12.00'],
            ],
            'splits' => [
                $ownerAId => ['enabled' => '1'],
            ],
        ]);

        $this->assertNotNull($service->detailContext($ownerAId, (string) $householdA['slug'], (int) $created['id']));
        $this->assertNull($service->detailContext($ownerBId, (string) $householdB['slug'], (int) $created['id']));
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

    private function expenseService(BaseConnection $db): ExpenseService
    {
        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );

        return new ExpenseService(
            db: $db,
            householdAuthorizationService: $authorization,
            expenseValidationService: new ExpenseValidationService(new SplitCalculationService(), new ExpenseCategoryModel($db)),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            expenseCategoryModel: new ExpenseCategoryModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            auditLogModel: new AuditLogModel($db),
        );
    }
}
