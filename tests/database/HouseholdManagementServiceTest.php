<?php

declare(strict_types=1);

use App\Database\Seeds\CoreAuthorizationSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Households\HouseholdModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdContextService;
use App\Services\Households\HouseholdManagementService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class HouseholdManagementServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = CoreAuthorizationSeeder::class;

    public function testSetCurrentAndUpdateSettingsPersistAuditSnapshots(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-settings@example.test', 'Owner Settings');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Settings');
        $authorization = $this->authorization($db);
        $session = service('session');
        $session->remove('app.active_household');

        $service = new HouseholdManagementService(
            db: $db,
            householdContextService: new HouseholdContextService(
                session: $session,
                authorization: $authorization,
                membershipModel: new HouseholdMembershipModel($db),
                userPreferenceModel: new UserPreferenceModel($db),
            ),
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $membership = $service->setCurrent($ownerId, (string) $household['slug']);
        $this->assertNotNull($membership);
        $this->assertSame((string) $household['slug'], $session->get('app.active_household'));

        $updated = $service->updateSettings($ownerId, (string) $household['slug'], [
            'name' => 'Casa Settings Plus',
            'description' => 'Household aggiornata',
            'base_currency' => 'USD',
            'timezone' => 'UTC',
            'simplify_debts' => '0',
            'chore_scoring_enabled' => '1',
            'avatar_path' => null,
            'locale' => 'en',
        ]);

        $this->assertNotNull($updated);
        $this->assertSame('Casa Settings Plus', $updated['household']['name']);
        $this->assertSame('USD', $updated['household']['base_currency']);
        $this->assertSame('UTC', $updated['household']['timezone']);
        $this->assertSame('en', $updated['settings']['locale']);

        $auditRow = (new AuditLogModel($db))
            ->where('entity_type', 'household')
            ->where('entity_id', (int) $household['id'])
            ->where('action', 'household.settings_updated')
            ->first();

        $this->assertNotNull($auditRow);
        $this->assertNotNull($auditRow['before_json']);
        $this->assertNotNull($auditRow['after_json']);
        $this->assertStringContainsString('"base_currency":"EUR"', (string) $auditRow['before_json']);
        $this->assertStringContainsString('"base_currency":"USD"', (string) $auditRow['after_json']);
    }

    public function testUserCanSwitchBetweenMultipleSeparateHouseholds(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-multi@example.test', 'Owner Multi');
        $firstHousehold = $this->provisionHousehold($db, $ownerId, 'Casa Alpha');
        $secondHousehold = $this->provisionHousehold($db, $ownerId, 'Casa Beta');
        $authorization = $this->authorization($db);
        $session = service('session');
        $session->remove('app.active_household');

        $service = new HouseholdManagementService(
            db: $db,
            householdContextService: new HouseholdContextService(
                session: $session,
                authorization: $authorization,
                membershipModel: new HouseholdMembershipModel($db),
                userPreferenceModel: new UserPreferenceModel($db),
            ),
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            householdSettingModel: new HouseholdSettingModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $available = $service->listForUser($ownerId);

        $this->assertCount(2, $available);
        $this->assertSame(['Casa Alpha', 'Casa Beta'], array_column($available, 'household_name'));

        $firstMembership = $service->setCurrent($ownerId, (string) $firstHousehold['slug']);
        $this->assertNotNull($firstMembership);
        $this->assertSame((string) $firstHousehold['slug'], $session->get('app.active_household'));

        $secondMembership = $service->setCurrent($ownerId, (string) $secondHousehold['slug']);
        $this->assertNotNull($secondMembership);
        $this->assertSame((string) $secondHousehold['slug'], $session->get('app.active_household'));

        $preferences = (new UserPreferenceModel($db))->findByUserId($ownerId);

        $this->assertNotNull($preferences);
        $this->assertSame((int) $secondHousehold['id'], (int) $preferences['default_household_id']);
        $this->assertSame((int) $firstHousehold['id'], (int) $firstMembership['household_id']);
        $this->assertSame((int) $secondHousehold['id'], (int) $secondMembership['household_id']);

        $activeContext = new HouseholdContextService(
            session: $session,
            authorization: $authorization,
            membershipModel: new HouseholdMembershipModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
        );

        $resolved = $activeContext->activeHousehold();

        $this->assertNotNull($resolved);
        $this->assertSame((string) $secondHousehold['slug'], $resolved['household_slug']);
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
            rolePermissionModel: new \App\Models\Authorization\RolePermissionModel($db),
        );
    }
}
