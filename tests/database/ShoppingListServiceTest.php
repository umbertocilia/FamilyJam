<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Shopping\ShoppingListService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ShoppingListServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreateUpdateAndDeleteShoppingListLifecycle(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-shopping-list@example.test', 'Owner Shopping');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Shopping');
        $service = $this->service($db);

        $created = $service->create($ownerId, (string) $household['slug'], [
            'name' => 'Spesa weekend',
            'is_default' => '1',
        ]);

        $this->assertSame('Spesa weekend', $created['name']);
        $this->assertSame(1, (int) $created['is_default']);

        $updated = $service->update($ownerId, (string) $household['slug'], (int) $created['id'], [
            'name' => 'Spesa domenica',
            'is_default' => '1',
        ]);

        $this->assertSame('Spesa domenica', $updated['name']);

        $service->softDelete($ownerId, (string) $household['slug'], (int) $created['id']);

        $deleted = (new ShoppingListModel($db))->findDetailForHousehold((int) $household['id'], (int) $created['id'], true);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted['deleted_at']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('shopping_list', (int) $created['id'], (int) $household['id']),
        );

        $this->assertSame(['shopping_list.created', 'shopping_list.updated', 'shopping_list.deleted'], $auditActions);
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

    private function service(BaseConnection $db): ShoppingListService
    {
        return new ShoppingListService(
            db: $db,
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new \App\Models\Authorization\RolePermissionModel($db),
            ),
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            shoppingListModel: new ShoppingListModel($db),
            shoppingItemModel: new \App\Models\Shopping\ShoppingItemModel($db),
            expenseCategoryModel: new ExpenseCategoryModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
