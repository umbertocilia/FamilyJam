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
use App\Models\Finance\SettlementModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Balances\BalanceService;
use App\Services\Balances\DebtSimplificationService;
use App\Services\Balances\SettlementService;
use App\Services\Expenses\ExpenseService;
use App\Services\Expenses\ExpenseValidationService;
use App\Services\Expenses\SplitCalculationService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SettlementServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testSettlementUpdatesLedgerWithoutChangingSimplificationNature(): void
    {
        $db = db_connect($this->DBGroup);
        $aliceId = $this->createUser($db, 'alice-balance@example.test', 'Alice');
        $bobId = $this->createUser($db, 'bob-balance@example.test', 'Bob');
        $carlaId = $this->createUser($db, 'carla-balance@example.test', 'Carla');
        $household = $this->provisionHousehold($db, $aliceId, 'Casa Ledger', true);
        $this->attachMemberRole($db, (int) $household['id'], $bobId, $aliceId);
        $this->attachMemberRole($db, (int) $household['id'], $carlaId, $aliceId);

        $expenseService = $this->expenseService($db);
        $balanceService = $this->balanceService($db);
        $settlementService = $this->settlementService($db, $balanceService);

        $expenseService->create($aliceId, (string) $household['slug'], [
            'title' => 'Groceries',
            'description' => null,
            'expense_date' => '2026-04-01',
            'currency' => 'EUR',
            'total_amount' => '60.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $aliceId => ['enabled' => '1', 'amount' => '60.00'],
            ],
            'splits' => [
                $aliceId => ['enabled' => '1'],
                $bobId => ['enabled' => '1'],
                $carlaId => ['enabled' => '1'],
            ],
        ]);

        $expenseService->create($bobId, (string) $household['slug'], [
            'title' => 'Utilities',
            'description' => null,
            'expense_date' => '2026-04-02',
            'currency' => 'EUR',
            'total_amount' => '30.00',
            'category_id' => '',
            'split_method' => 'exact',
            'payers' => [
                $bobId => ['enabled' => '1', 'amount' => '30.00'],
            ],
            'splits' => [
                $bobId => ['enabled' => '1', 'owed_amount' => '15.00'],
                $carlaId => ['enabled' => '1', 'owed_amount' => '15.00'],
            ],
        ]);

        $before = $balanceService->overviewContext($aliceId, (string) $household['slug']);

        $this->assertNotNull($before);
        $netRows = $before['netBalances']['EUR'];
        $this->assertSame('40.00', $this->findNetAmountForUser($netRows, $aliceId));
        $this->assertSame('-5.00', $this->findNetAmountForUser($netRows, $bobId));
        $this->assertSame('-35.00', $this->findNetAmountForUser($netRows, $carlaId));
        $this->assertCount(3, $before['pairwiseBalances']['EUR']);
        $this->assertCount(2, $before['simplifiedTransfers']['EUR']);

        $settlement = $settlementService->create($carlaId, (string) $household['slug'], [
            'from_user_id' => (string) $carlaId,
            'to_user_id' => (string) $aliceId,
            'settlement_date' => '2026-04-03',
            'currency' => 'EUR',
            'amount' => '10.00',
            'payment_method' => 'bank transfer',
            'note' => 'Pagamento parziale',
        ]);

        $this->assertSame('10.00', $settlement['amount']);

        $after = $balanceService->overviewContext($aliceId, (string) $household['slug']);

        $this->assertNotNull($after);
        $netRows = $after['netBalances']['EUR'];
        $this->assertSame('30.00', $this->findNetAmountForUser($netRows, $aliceId));
        $this->assertSame('-5.00', $this->findNetAmountForUser($netRows, $bobId));
        $this->assertSame('-25.00', $this->findNetAmountForUser($netRows, $carlaId));

        $realPairwise = $after['pairwiseBalances']['EUR'];
        $this->assertCount(3, $realPairwise);
        $this->assertSame('20.00', $this->findPairwiseAmount($realPairwise, $bobId, $aliceId));
        $this->assertSame('10.00', $this->findPairwiseAmount($realPairwise, $carlaId, $aliceId));
        $this->assertSame('15.00', $this->findPairwiseAmount($realPairwise, $carlaId, $bobId));

        $simplified = $after['simplifiedTransfers']['EUR'];
        $this->assertCount(2, $simplified);
        $this->assertSame('5.00', $this->findPairwiseAmount($simplified, $bobId, $aliceId));
        $this->assertSame('25.00', $this->findPairwiseAmount($simplified, $carlaId, $aliceId));

        $settlements = (new SettlementModel($db))->listForHousehold((int) $household['id']);
        $this->assertCount(1, $settlements);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('settlement', (int) $settlement['id'], (int) $household['id']),
        );
        $this->assertSame(['settlement.created'], $auditActions);

        $auditRows = (new AuditLogModel($db))->listForEntity('settlement', (int) $settlement['id'], (int) $household['id']);
        $this->assertCount(1, $auditRows);
        $this->assertNull($auditRows[0]['before_json']);
        $this->assertNotNull($auditRows[0]['after_json']);
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
    private function provisionHousehold(BaseConnection $db, int $ownerId, string $name, bool $simplifyDebts): array
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
            'simplify_debts' => $simplifyDebts,
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
            expenseValidationService: new ExpenseValidationService(new SplitCalculationService()),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            auditLogModel: new AuditLogModel($db),
        );
    }

    private function balanceService(BaseConnection $db): BalanceService
    {
        return new BalanceService(
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            settlementModel: new SettlementModel($db),
            debtSimplificationService: new DebtSimplificationService(),
        );
    }

    private function settlementService(BaseConnection $db, BalanceService $balanceService): SettlementService
    {
        return new SettlementService(
            db: $db,
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            settlementModel: new SettlementModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            balanceService: $balanceService,
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function findNetAmountForUser(array $rows, int $userId): string
    {
        foreach ($rows as $row) {
            if ((int) $row['user_id'] === $userId) {
                return (string) $row['net_amount'];
            }
        }

        self::fail('User not found in net rows: ' . $userId);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function findPairwiseAmount(array $rows, int $fromUserId, int $toUserId): string
    {
        foreach ($rows as $row) {
            if ((int) $row['from_user_id'] === $fromUserId && (int) $row['to_user_id'] === $toUserId) {
                return (string) $row['amount'];
            }
        }

        self::fail(sprintf('Pairwise row not found: %d -> %d', $fromUserId, $toUserId));
    }
}
