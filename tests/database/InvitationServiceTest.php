<?php

declare(strict_types=1);

use App\Database\Seeds\CoreAuthorizationSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\InvitationModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Auth\OutboundEmailService;
use App\Services\Households\HouseholdContextService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Households\InvitationService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class InvitationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = CoreAuthorizationSeeder::class;

    public function testCreateAndAcceptInvitationCreatesActiveMembership(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner@example.test', 'Owner User');
        $inviteeId = $this->createUser($db, 'invitee@example.test', 'Invitee User');

        $household = (new HouseholdProvisioningService(
            db: $db,
            householdModel: new HouseholdModel($db),
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            roleModel: new RoleModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        ))->create($ownerId, [
            'name' => 'Casa Inviti',
            'locale' => 'it',
        ]);

        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new \App\Models\Authorization\RolePermissionModel($db),
        );

        $service = new InvitationService(
            db: $db,
            householdAuthorizationService: $authorization,
            invitationModel: new InvitationModel($db),
            userModel: new UserModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            roleModel: new RoleModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            householdContextService: new HouseholdContextService(
                session: service('session'),
                authorization: $authorization,
                membershipModel: new HouseholdMembershipModel($db),
                userPreferenceModel: new UserPreferenceModel($db),
            ),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $invitation = $service->create($ownerId, (string) $household['slug'], [
            'email' => 'invitee@example.test',
            'role_code' => 'member',
            'message' => 'Join us',
        ]);

        $token = $this->extractRawInvitationToken($db, (int) $invitation['id']);
        $membership = $service->accept($token, $inviteeId);

        $this->assertNotNull($membership);
        $this->assertSame('active', $membership['status']);
        $this->assertSame($household['slug'], $membership['household_slug']);

        $auditRows = (new AuditLogModel($db))->where('entity_type', 'invitation')->orderBy('id', 'ASC')->findAll();
        $this->assertCount(2, $auditRows);
        $this->assertSame('invitation.created', $auditRows[0]['action']);
        $this->assertNull($auditRows[0]['before_json']);
        $this->assertNotNull($auditRows[0]['after_json']);
        $this->assertSame('invitation.accepted', $auditRows[1]['action']);
        $this->assertNotNull($auditRows[1]['before_json']);
        $this->assertNotNull($auditRows[1]['after_json']);
    }

    public function testAcceptInvitationResetsHistoricalRolesOnRestoredMembership(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-restore@example.test', 'Owner Restore');
        $inviteeId = $this->createUser($db, 'restoree@example.test', 'Restoree User');

        $household = (new HouseholdProvisioningService(
            db: $db,
            householdModel: new HouseholdModel($db),
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            roleModel: new RoleModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        ))->create($ownerId, [
            'name' => 'Casa Restore',
            'locale' => 'it',
        ]);

        $membershipModel = new HouseholdMembershipModel($db);
        $membershipRoleModel = new MembershipRoleModel($db);
        $roleModel = new RoleModel($db);
        $adminRole = $roleModel->findByCode('admin', (int) $household['id']);

        $restoredMembershipId = (int) $membershipModel->insert([
            'household_id' => (int) $household['id'],
            'user_id' => $inviteeId,
            'invited_by_user_id' => $ownerId,
            'status' => 'inactive',
            'nickname' => null,
            'joined_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'deleted_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ], true);

        $this->assertNotNull($adminRole);
        $membershipRoleModel->insert([
            'membership_id' => $restoredMembershipId,
            'role_id' => (int) $adminRole['id'],
            'assigned_by_user_id' => $ownerId,
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);

        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new \App\Models\Authorization\RolePermissionModel($db),
        );

        $service = new InvitationService(
            db: $db,
            householdAuthorizationService: $authorization,
            invitationModel: new InvitationModel($db),
            userModel: new UserModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            roleModel: new RoleModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            householdContextService: new HouseholdContextService(
                session: service('session'),
                authorization: $authorization,
                membershipModel: new HouseholdMembershipModel($db),
                userPreferenceModel: new UserPreferenceModel($db),
            ),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $invitation = $service->create($ownerId, (string) $household['slug'], [
            'email' => 'restoree@example.test',
            'role_code' => 'member',
        ]);

        $token = $this->extractRawInvitationToken($db, (int) $invitation['id']);
        $membership = $service->accept($token, $inviteeId);

        $this->assertNotNull($membership);
        $this->assertStringContainsString('member', (string) $membership['role_codes']);
        $this->assertStringNotContainsString('admin', (string) $membership['role_codes']);
    }

    public function testRevokeInvitationWritesBeforeAndAfterAuditSnapshots(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-revoke@example.test', 'Owner Revoke');
        $household = (new HouseholdProvisioningService(
            db: $db,
            householdModel: new HouseholdModel($db),
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            roleModel: new RoleModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        ))->create($ownerId, [
            'name' => 'Casa Revoke',
            'locale' => 'it',
        ]);

        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new \App\Models\Authorization\RolePermissionModel($db),
        );

        $service = new InvitationService(
            db: $db,
            householdAuthorizationService: $authorization,
            invitationModel: new InvitationModel($db),
            userModel: new UserModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            roleModel: new RoleModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            householdContextService: new HouseholdContextService(
                session: service('session'),
                authorization: $authorization,
                membershipModel: new HouseholdMembershipModel($db),
                userPreferenceModel: new UserPreferenceModel($db),
            ),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $invitation = $service->create($ownerId, (string) $household['slug'], [
            'email' => 'revokee@example.test',
            'role_code' => 'member',
        ]);

        $service->revoke($ownerId, (string) $household['slug'], (int) $invitation['id']);

        $auditRows = (new AuditLogModel($db))
            ->where('entity_type', 'invitation')
            ->where('entity_id', (int) $invitation['id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $this->assertCount(2, $auditRows);
        $this->assertSame('invitation.created', $auditRows[0]['action']);
        $this->assertSame('invitation.revoked', $auditRows[1]['action']);
        $this->assertNotNull($auditRows[1]['before_json']);
        $this->assertNotNull($auditRows[1]['after_json']);
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

    private function extractRawInvitationToken(\CodeIgniter\Database\BaseConnection $db, int $invitationId): string
    {
        $row = (new InvitationModel($db))->find($invitationId);
        $this->assertNotNull($row);

        // The service stores only the hash, so in the DB test we replace it with a deterministic token.
        $rawToken = bin2hex(random_bytes(16));
        (new InvitationModel($db))->update($invitationId, [
            'token_hash' => hash('sha256', $rawToken),
        ]);

        return $rawToken;
    }
}
