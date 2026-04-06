<?php

declare(strict_types=1);

use App\Authorization\Permission;
use App\Authorization\SystemRole;
use App\Database\Seeds\CoreAuthorizationSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Models\Authorization\RoleModel;
use App\Models\Households\HouseholdModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Auth\UserPreferenceModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class HouseholdProvisioningServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = CoreAuthorizationSeeder::class;

    public function testCreateCreatesOwnerMembershipAndAuditEntry(): void
    {
        $ownerUserId = $this->createUser('owner@example.test', 'Owner One');
        $db = db_connect($this->DBGroup);
        $service = $this->makeProvisioningService();

        $household = $service->create($ownerUserId, [
            'name' => 'Casa Aurora',
            'base_currency' => 'EUR',
            'timezone' => 'Europe/Rome',
            'locale' => 'it',
            'simplify_debts' => true,
        ]);

        $this->assertSame('casa-aurora', $household['slug']);

        $membership = (new HouseholdMembershipModel($db))->findActiveMembership((int) $household['id'], $ownerUserId);

        $this->assertNotNull($membership);
        $this->assertStringContainsString(SystemRole::OWNER, (string) $membership['role_codes']);
        $this->assertTrue(
            (new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ))->can($ownerUserId, (int) $household['id'], Permission::MANAGE_HOUSEHOLD),
        );

        $auditLog = (new AuditLogModel($db))
            ->where('household_id', (int) $household['id'])
            ->where('action', 'household.created')
            ->first();

        $this->assertNotNull($auditLog);
    }

    public function testCreateGeneratesUniqueSlugWhenNamesCollide(): void
    {
        $ownerUserId = $this->createUser('owner-two@example.test', 'Owner Two');
        $service = $this->makeProvisioningService();

        $first = $service->create($ownerUserId, ['name' => 'Casa Aurora']);
        $second = $service->create($ownerUserId, ['name' => 'Casa Aurora']);

        $this->assertSame('casa-aurora', $first['slug']);
        $this->assertSame('casa-aurora-2', $second['slug']);
    }

    private function createUser(string $email, string $displayName): int
    {
        return (int) (new UserModel(db_connect($this->DBGroup)))->insert([
            'email' => $email,
            'password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
            'display_name' => $displayName,
            'locale' => 'it',
            'timezone' => 'Europe/Rome',
            'status' => 'active',
        ], true);
    }

    private function makeProvisioningService(): HouseholdProvisioningService
    {
        $db = db_connect($this->DBGroup);

        return new HouseholdProvisioningService(
            db: $db,
            householdModel: new HouseholdModel($db),
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            roleModel: new RoleModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
