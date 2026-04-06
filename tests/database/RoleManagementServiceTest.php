<?php

declare(strict_types=1);

use App\Authorization\Permission;
use App\Authorization\SystemRole;
use App\Database\Seeds\CoreAuthorizationSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\PermissionModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Households\HouseholdModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Authorization\RoleManagementService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DomainException;

/**
 * @internal
 */
final class RoleManagementServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = CoreAuthorizationSeeder::class;

    public function testCreateCustomRoleAndAssignItToMembershipUpdatesEffectivePermissions(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-rbac@example.test', 'Owner RBAC');
        $memberId = $this->createUser($db, 'member-rbac@example.test', 'Member RBAC');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa RBAC');
        $memberRole = (new RoleModel($db))->findByCode(SystemRole::MEMBER, (int) $household['id']);
        $this->assertNotNull($memberRole);

        $membershipId = (new HouseholdMembershipModel($db))->insert([
            'household_id' => (int) $household['id'],
            'user_id' => $memberId,
            'invited_by_user_id' => $ownerId,
            'status' => 'active',
            'nickname' => null,
            'joined_at' => date('Y-m-d H:i:s'),
        ], true);

        (new MembershipRoleModel($db))->insert([
            'membership_id' => $membershipId,
            'role_id' => (int) $memberRole['id'],
            'assigned_by_user_id' => $ownerId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $authorization = $this->authorization($db);
        $service = $this->roleManager($db, $authorization);

        $customRole = $service->createRole($ownerId, (string) $household['slug'], [
            'name' => 'Finance Editor',
            'code' => 'finance_editor',
            'description' => 'Can edit finance records.',
            'permission_codes' => [
                Permission::EDIT_ANY_EXPENSE,
                Permission::VIEW_REPORTS,
            ],
        ]);

        $service->assignRolesToMembership($ownerId, (string) $household['slug'], (int) $membershipId, [
            'role_ids' => [(int) $memberRole['id'], (int) $customRole['id']],
        ]);

        $this->assertTrue($authorization->hasRole($memberId, (string) $household['slug'], 'finance_editor'));
        $this->assertTrue($authorization->hasPermission($memberId, (string) $household['slug'], Permission::EDIT_ANY_EXPENSE));
        $this->assertTrue($authorization->canManage($memberId, (string) $household['slug'], 'edit_expense', ['created_by' => $ownerId]));

        $auditRows = (new AuditLogModel($db))
            ->whereIn('action', ['role.created', 'membership.roles_updated'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $this->assertCount(2, $auditRows);
        $this->assertSame('role.created', $auditRows[0]['action']);
        $this->assertNotNull($auditRows[0]['after_json']);
        $this->assertSame('membership.roles_updated', $auditRows[1]['action']);
        $this->assertNotNull($auditRows[1]['before_json']);
        $this->assertNotNull($auditRows[1]['after_json']);
    }

    public function testUpdateRoleRejectsSystemRole(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-system-role@example.test', 'Owner System');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa System');
        $memberRole = (new RoleModel($db))->findByCode(SystemRole::MEMBER, (int) $household['id']);
        $this->assertNotNull($memberRole);

        $this->expectException(DomainException::class);

        $this->roleManager($db, $this->authorization($db))->updateRole($ownerId, (string) $household['slug'], (int) $memberRole['id'], [
            'name' => 'Broken',
            'code' => 'broken',
            'permission_codes' => [Permission::VIEW_REPORTS],
        ]);
    }

    public function testAssignRolesPreservesLockedOwnerRole(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-locked@example.test', 'Owner Locked');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Locked');
        $ownerMembership = (new HouseholdMembershipModel($db))->findActiveMembership((int) $household['id'], $ownerId);

        $this->assertNotNull($ownerMembership);

        $this->roleManager($db, $this->authorization($db))->assignRolesToMembership($ownerId, (string) $household['slug'], (int) $ownerMembership['id'], [
            'role_ids' => [],
        ]);

        $refreshed = (new HouseholdMembershipModel($db))->findActiveMembership((int) $household['id'], $ownerId);

        $this->assertNotNull($refreshed);
        $this->assertStringContainsString(SystemRole::OWNER, (string) $refreshed['role_codes']);
    }

    private function createUser(\CodeIgniter\Database\BaseConnection $db, string $email, string $displayName): int
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
    private function provisionHousehold(\CodeIgniter\Database\BaseConnection $db, int $ownerId, string $name): array
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

    private function authorization(\CodeIgniter\Database\BaseConnection $db): HouseholdAuthorizationService
    {
        return new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );
    }

    private function roleManager(\CodeIgniter\Database\BaseConnection $db, HouseholdAuthorizationService $authorization): RoleManagementService
    {
        return new RoleManagementService(
            db: $db,
            householdAuthorizationService: $authorization,
            roleModel: new RoleModel($db),
            permissionModel: new PermissionModel($db),
            rolePermissionModel: new RolePermissionModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
