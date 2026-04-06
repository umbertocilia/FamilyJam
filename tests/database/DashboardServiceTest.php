<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Finance\SettlementModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Notifications\NotificationModel;
use App\Models\Pinboard\PinboardPostModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Audit\AuditLogService;
use App\Services\Balances\BalanceService;
use App\Services\Balances\DebtSimplificationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Notifications\NotificationService;
use App\Services\Reports\DashboardService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class DashboardServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testHouseholdDashboardAggregatesModulesIntoSingleContext(): void
    {
        $db = db_connect($this->DBGroup);
        $today = new DateTimeImmutable('today');
        $expenseDate = $today->format('Y-m-d');
        $dueTomorrow = $today->modify('+1 day')->format('Y-m-d') . ' 18:00:00';
        $ownerId = $this->createUser($db, 'owner-dashboard@example.test', 'Owner Dashboard');
        $memberId = $this->createUser($db, 'member-dashboard@example.test', 'Member Dashboard');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Dashboard');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $expenseId = (int) (new ExpenseModel($db))->insert([
            'household_id' => (int) $household['id'],
            'title' => 'Pranzo condiviso',
            'description' => null,
            'expense_date' => $expenseDate,
            'currency' => 'EUR',
            'total_amount' => '30.00',
            'split_method' => 'equal',
            'status' => 'active',
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        (new ExpensePayerModel($db))->insert(['expense_id' => $expenseId, 'user_id' => $ownerId, 'amount_paid' => '30.00']);
        (new ExpenseSplitModel($db))->insertBatch([
            ['expense_id' => $expenseId, 'user_id' => $ownerId, 'owed_amount' => '15.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
            ['expense_id' => $expenseId, 'user_id' => $memberId, 'owed_amount' => '15.00', 'percentage' => null, 'share_units' => null, 'is_excluded' => 0],
        ]);

        $choreId = (int) $db->table('chores')->insert([
            'household_id' => (int) $household['id'],
            'title' => 'Cucina',
            'description' => null,
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $memberId,
            'points' => 3,
            'estimated_minutes' => 15,
            'is_active' => 1,
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
            'created_at' => $today->format('Y-m-d') . ' 09:00:00',
            'updated_at' => $today->format('Y-m-d') . ' 09:00:00',
        ]) ? (int) $db->insertID() : 0;
        $this->assertGreaterThan(0, $choreId);

        (new ChoreOccurrenceModel($db))->insert([
            'household_id' => (int) $household['id'],
            'chore_id' => $choreId,
            'assigned_user_id' => $memberId,
            'due_at' => $dueTomorrow,
            'status' => 'pending',
            'points_awarded' => 0,
        ]);

        $listId = (int) (new ShoppingListModel($db))->insert([
            'household_id' => (int) $household['id'],
            'name' => 'Spesa veloce',
            'is_default' => 1,
            'created_by' => $ownerId,
        ], true);
        (new ShoppingItemModel($db))->insert([
            'shopping_list_id' => $listId,
            'household_id' => (int) $household['id'],
            'name' => 'Carta cucina',
            'quantity' => '2',
            'unit' => 'pz',
            'priority' => 'urgent',
            'position' => 1,
            'is_purchased' => 0,
            'created_by' => $ownerId,
        ]);

        $postId = (int) (new PinboardPostModel($db))->insert([
            'household_id' => (int) $household['id'],
            'author_user_id' => $ownerId,
            'title' => 'Riordino weekend',
            'body' => 'Da fare sabato mattina',
            'post_type' => 'note',
            'is_pinned' => 1,
        ], true);

        $this->assertGreaterThan(0, $postId);

        (new NotificationModel($db))->insert([
            'user_id' => $ownerId,
            'household_id' => (int) $household['id'],
            'type' => 'expense_created',
            'title' => 'Nuova spesa registrata',
            'body' => 'Pranzo condiviso',
            'data_json' => json_encode(['expense_id' => $expenseId, 'household_slug' => (string) $household['slug']], JSON_THROW_ON_ERROR),
        ]);

        $service = $this->service($db);
        $context = $service->householdContext($ownerId, (string) $household['slug']);

        $this->assertNotNull($context);
        $this->assertSame('Casa Dashboard', $context['household']['name']);
        $this->assertNotEmpty($context['recent_expenses']);
        $this->assertNotEmpty($context['upcoming_chores']);
        $this->assertNotEmpty($context['urgent_shopping_items']);
        $this->assertNotEmpty($context['recent_posts']);
        $this->assertNotEmpty($context['recent_notifications']);
        $this->assertSame(1, $context['summary']['urgent_items']);
        $this->assertSame(1, $context['summary']['unread_notifications']);
        $this->assertSame('Pranzo condiviso', $context['recent_expenses'][0]['title']);
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

    private function service(BaseConnection $db): DashboardService
    {
        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );

        return new DashboardService(
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            balanceService: new BalanceService(
                householdAuthorizationService: $authorization,
                householdModel: new HouseholdModel($db),
                householdMembershipModel: new HouseholdMembershipModel($db),
                expenseModel: new ExpenseModel($db),
                expensePayerModel: new ExpensePayerModel($db),
                expenseSplitModel: new ExpenseSplitModel($db),
                settlementModel: new SettlementModel($db),
                debtSimplificationService: new DebtSimplificationService(),
            ),
            expenseModel: new ExpenseModel($db),
            choreOccurrenceModel: new ChoreOccurrenceModel($db),
            shoppingItemModel: new ShoppingItemModel($db),
            pinboardPostModel: new PinboardPostModel($db),
            notificationService: new NotificationService(
                db: $db,
                notificationModel: new NotificationModel($db),
            ),
        );
    }
}
