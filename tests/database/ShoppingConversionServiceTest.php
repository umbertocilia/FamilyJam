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
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Expenses\ExpenseService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Shopping\ShoppingConversionService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ShoppingConversionServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testConvertPurchasedItemsToExpenseLinksItems(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-shopping-convert@example.test', 'Owner Convert');
        $memberId = $this->createUser($db, 'member-shopping-convert@example.test', 'Member Convert');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Convert');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $listId = (int) (new ShoppingListModel($db))->insert([
            'household_id' => (int) $household['id'],
            'name' => 'Mercato',
            'is_default' => 1,
            'created_by' => $ownerId,
        ], true);

        $firstItemId = (int) (new ShoppingItemModel($db))->insert([
            'shopping_list_id' => $listId,
            'household_id' => (int) $household['id'],
            'name' => 'Pomodori',
            'quantity' => '2.00',
            'unit' => 'kg',
            'category' => 'groceries',
            'priority' => 'normal',
            'position' => 1,
            'is_purchased' => 1,
            'purchased_at' => date('Y-m-d H:i:s'),
            'purchased_by' => $ownerId,
            'created_by' => $ownerId,
        ], true);
        $secondItemId = (int) (new ShoppingItemModel($db))->insert([
            'shopping_list_id' => $listId,
            'household_id' => (int) $household['id'],
            'name' => 'Mozzarella',
            'quantity' => '3.00',
            'unit' => 'pz',
            'category' => 'groceries',
            'priority' => 'high',
            'position' => 2,
            'is_purchased' => 1,
            'purchased_at' => date('Y-m-d H:i:s'),
            'purchased_by' => $ownerId,
            'created_by' => $ownerId,
        ], true);

        $category = (new ExpenseCategoryModel($db))->findAvailableForHousehold((int) $household['id'], 1) ?? (new ExpenseCategoryModel($db))->listAvailableForHousehold((int) $household['id'])[0];

        $expense = $this->service($db)->convertPurchasedItemsToExpense($ownerId, (string) $household['slug'], $listId, [
            'item_ids' => [$firstItemId, $secondItemId],
            'title' => 'Spesa mercato',
            'total_amount' => '24.50',
            'expense_date' => '2026-04-02',
            'payer_user_id' => $ownerId,
            'participant_user_ids' => [$ownerId, $memberId],
            'category_id' => $category['id'],
        ]);

        $this->assertSame('Spesa mercato', $expense['title']);

        $storedExpense = (new ExpenseModel($db))->find((int) $expense['id']);
        $this->assertNotNull($storedExpense);

        $firstItem = (new ShoppingItemModel($db))->find((int) $firstItemId);
        $secondItem = (new ShoppingItemModel($db))->find((int) $secondItemId);
        $this->assertSame((int) $expense['id'], (int) $firstItem['converted_expense_id']);
        $this->assertSame((int) $expense['id'], (int) $secondItem['converted_expense_id']);
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
        $membershipId = (int) (new HouseholdMembershipModel($db))->insert([
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

    private function service(BaseConnection $db): ShoppingConversionService
    {
        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );

        return new ShoppingConversionService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            shoppingListModel: new ShoppingListModel($db),
            shoppingItemModel: new ShoppingItemModel($db),
            expenseCategoryModel: new ExpenseCategoryModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            expenseService: new ExpenseService(db: $db, householdAuthorizationService: $authorization),
        );
    }
}
