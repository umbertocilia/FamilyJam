<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Chores\ChoreModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Chores\ChoreFairnessService;
use App\Services\Chores\ChoreOccurrenceService;
use App\Services\Chores\ChoreRotationService;
use App\Services\Chores\ChoreService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Recurring\RecurringScheduleService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ChoreServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreatePersistsTemplateRecurringRuleAndFirstOccurrence(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-chore-service@example.test', 'Owner Service');
        $memberId = $this->createUser($db, 'member-chore-service@example.test', 'Member Service');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Service');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $created = $this->service($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Pulizia salotto',
            'description' => 'Passare aspirapolvere e spolverare',
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $memberId,
            'points' => 4,
            'estimated_minutes' => 25,
            'is_active' => '1',
            'first_due_at' => '2026-04-03T18:30',
            'recurring_enabled' => '1',
            'frequency' => 'weekly',
            'interval_value' => '1',
            'starts_at' => '2026-04-03T18:30',
            'ends_at' => '',
            'by_weekday' => ['5'],
            'day_of_month' => '',
            'custom_unit' => '',
        ]);

        $this->assertSame('Pulizia salotto', $created['title']);
        $this->assertSame('fixed', $created['assignment_mode']);
        $this->assertNotEmpty($created['recurring_rule_id']);

        $rule = (new RecurringRuleModel($db))->find((int) $created['recurring_rule_id']);
        $this->assertNotNull($rule);
        $this->assertSame('chore', $rule['entity_type']);
        $this->assertSame('weekly', $rule['frequency']);

        $occurrences = (new \App\Models\Chores\ChoreOccurrenceModel($db))->listForChore((int) $household['id'], (int) $created['id']);
        $this->assertCount(1, $occurrences);
        $this->assertSame($memberId, (int) $occurrences[0]['assigned_user_id']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('chore', (int) $created['id'], (int) $household['id']),
        );
        $this->assertSame(['chore.created'], $auditActions);
    }

    public function testUpdateAndToggleActiveRefreshRecurringState(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-chore-toggle@example.test', 'Owner Toggle');
        $memberId = $this->createUser($db, 'member-chore-toggle@example.test', 'Member Toggle');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Toggle');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $created = $this->service($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Lavastoviglie',
            'description' => null,
            'assignment_mode' => 'rotation',
            'rotation_anchor_user_id' => $memberId,
            'points' => 2,
            'estimated_minutes' => 10,
            'is_active' => '1',
            'first_due_at' => '',
            'recurring_enabled' => '1',
            'frequency' => 'daily',
            'interval_value' => '1',
            'starts_at' => '2026-04-03T08:00',
            'ends_at' => '',
            'by_weekday' => [],
            'day_of_month' => '',
            'custom_unit' => '',
        ]);

        $updated = $this->service($db)->update($ownerId, (string) $household['slug'], (int) $created['id'], [
            'title' => 'Lavastoviglie sera',
            'description' => 'Controllo e svuotamento',
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $ownerId,
            'points' => 3,
            'estimated_minutes' => 12,
            'is_active' => '1',
            'first_due_at' => '',
            'recurring_enabled' => '1',
            'frequency' => 'monthly',
            'interval_value' => '1',
            'starts_at' => '2026-04-05T21:00',
            'ends_at' => '',
            'by_weekday' => [],
            'day_of_month' => '5',
            'custom_unit' => '',
        ]);

        $this->assertSame('Lavastoviglie sera', $updated['title']);
        $this->assertSame('fixed', $updated['assignment_mode']);
        $this->assertSame($ownerId, (int) $updated['fixed_assignee_user_id']);

        $disabled = $this->service($db)->toggleActive($ownerId, (string) $household['slug'], (int) $created['id']);
        $this->assertSame(0, (int) $disabled['is_active']);

        $rule = (new RecurringRuleModel($db))->find((int) $created['recurring_rule_id']);
        $this->assertNotNull($rule);
        $this->assertSame(0, (int) $rule['is_active']);
        $this->assertNull($rule['next_run_at']);

        $enabled = $this->service($db)->toggleActive($ownerId, (string) $household['slug'], (int) $created['id']);
        $this->assertSame(1, (int) $enabled['is_active']);

        $rule = (new RecurringRuleModel($db))->find((int) $created['recurring_rule_id']);
        $this->assertNotNull($rule);
        $this->assertSame(1, (int) $rule['is_active']);
        $this->assertNotNull($rule['next_run_at']);
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
            'chore_scoring_enabled' => true,
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

    private function service(BaseConnection $db): ChoreService
    {
        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );
        $audit = new AuditLogService(new AuditLogModel($db));
        $occurrenceService = new ChoreOccurrenceService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            choreModel: new ChoreModel($db),
            choreOccurrenceModel: new \App\Models\Chores\ChoreOccurrenceModel($db),
            choreRotationService: new ChoreRotationService(),
            auditLogService: $audit,
        );

        return new ChoreService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            choreModel: new ChoreModel($db),
            choreOccurrenceModel: new \App\Models\Chores\ChoreOccurrenceModel($db),
            recurringRuleModel: new RecurringRuleModel($db),
            recurringScheduleService: new RecurringScheduleService(),
            choreOccurrenceService: $occurrenceService,
            choreFairnessService: new ChoreFairnessService(
                householdAuthorizationService: $authorization,
                householdModel: new HouseholdModel($db),
                choreOccurrenceModel: new \App\Models\Chores\ChoreOccurrenceModel($db),
            ),
            auditLogService: $audit,
        );
    }
}
