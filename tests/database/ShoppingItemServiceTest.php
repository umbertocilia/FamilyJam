<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Shopping\ShoppingItemService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ShoppingItemServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testQuickAddToggleBulkAndDeleteItem(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-shopping-item@example.test', 'Owner Items');
        $memberId = $this->createUser($db, 'member-shopping-item@example.test', 'Member Items');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Items');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $listId = (int) (new ShoppingListModel($db))->insert([
            'household_id' => (int) $household['id'],
            'name' => 'Supermercato',
            'is_default' => 1,
            'created_by' => $ownerId,
        ], true);

        $service = $this->service($db);
        $created = $service->quickAdd($ownerId, (string) $household['slug'], $listId, [
            'name' => 'Latte',
            'quantity' => '2',
            'unit' => 'L',
            'category' => 'groceries',
            'notes' => 'Intero',
            'priority' => 'urgent',
            'assigned_user_id' => $memberId,
        ]);

        $this->assertSame('Latte', $created['name']);
        $this->assertSame(0, (int) $created['is_purchased']);

        $toggled = $service->togglePurchased($ownerId, (string) $household['slug'], (int) $created['id']);
        $this->assertSame(1, (int) $toggled['is_purchased']);

        $secondId = (int) (new ShoppingItemModel($db))->insert([
            'shopping_list_id' => $listId,
            'household_id' => (int) $household['id'],
            'name' => 'Pane',
            'quantity' => '1.00',
            'unit' => null,
            'category' => 'groceries',
            'notes' => null,
            'priority' => 'normal',
            'assigned_user_id' => null,
            'position' => 2,
            'is_purchased' => 0,
            'created_by' => $ownerId,
        ], true);

        $bulk = $service->bulkPurchase($ownerId, (string) $household['slug'], $listId, [(int) $secondId], true);
        $this->assertCount(2, $bulk);

        $service->softDelete($ownerId, (string) $household['slug'], (int) $secondId);

        $deleted = (new ShoppingItemModel($db))->findDetailForHousehold((int) $household['id'], (int) $secondId, true);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted['deleted_at']);
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

    private function service(BaseConnection $db): ShoppingItemService
    {
        return new ShoppingItemService(
            db: $db,
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            householdMembershipModel: new HouseholdMembershipModel($db),
            shoppingListModel: new ShoppingListModel($db),
            shoppingItemModel: new ShoppingItemModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
