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
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\RecurringRuleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Chores\ChoreOccurrenceService;
use App\Services\Chores\ChoreRecurringExecutionService;
use App\Services\Chores\ChoreRotationService;
use App\Services\Recurring\RecurringScheduleService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class ChoreRecurringExecutionServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testRecurringRotationSkipsInactiveMembersAndAvoidsDuplicates(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-chore-rec@example.test', 'Owner Rotation');
        $bobId = $this->createUser($db, 'bob-chore-rec@example.test', 'Bob Rotation');
        $carlaId = $this->createUser($db, 'carla-chore-rec@example.test', 'Carla Rotation');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Rotation');
        $this->attachMemberRole($db, (int) $household['id'], $bobId, $ownerId);
        $carlaMembershipId = $this->attachMemberRole($db, (int) $household['id'], $carlaId, $ownerId);

        $ruleId = (new RecurringRuleModel($db))->insert([
            'household_id' => (int) $household['id'],
            'entity_type' => 'chore',
            'frequency' => 'daily',
            'interval_value' => 1,
            'by_weekday' => null,
            'day_of_month' => null,
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => null,
            'next_run_at' => '2026-04-03 09:00:00',
            'last_run_at' => null,
            'is_active' => 1,
            'config_json' => json_encode(['schedule' => ['custom_unit' => null], 'series' => ['version' => 1]], JSON_THROW_ON_ERROR),
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $choreId = (new ChoreModel($db))->insert([
            'household_id' => (int) $household['id'],
            'recurring_rule_id' => (int) $ruleId,
            'title' => 'Trash rotation',
            'description' => null,
            'assignment_mode' => 'rotation',
            'fixed_assignee_user_id' => null,
            'rotation_anchor_user_id' => $bobId,
            'points' => 2,
            'estimated_minutes' => 10,
            'is_active' => 1,
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $service = $this->executor($db);
        $first = $service->runDue(new DateTimeImmutable('2026-04-03 09:05:00'));
        (new RecurringRuleModel($db))->update((int) $ruleId, [
            'next_run_at' => '2026-04-03 09:00:00',
            'last_run_at' => null,
            'is_active' => 1,
        ]);
        $second = $service->runDue(new DateTimeImmutable('2026-04-03 09:05:00'));

        $this->assertSame(1, $first['generated_occurrences']);
        $this->assertSame(1, $second['skipped_duplicates']);

        $firstOccurrence = (new ChoreOccurrenceModel($db))->findByChoreAndDueAt((int) $choreId, '2026-04-03 09:00:00');
        $this->assertNotNull($firstOccurrence);
        $this->assertSame($bobId, (int) $firstOccurrence['assigned_user_id']);

        $service->runDue(new DateTimeImmutable('2026-04-04 09:05:00'));
        $secondOccurrence = (new ChoreOccurrenceModel($db))->findByChoreAndDueAt((int) $choreId, '2026-04-04 09:00:00');
        $this->assertNotNull($secondOccurrence);
        $this->assertSame($carlaId, (int) $secondOccurrence['assigned_user_id']);

        (new HouseholdMembershipModel($db))->update($carlaMembershipId, ['status' => 'inactive']);

        $service->runDue(new DateTimeImmutable('2026-04-05 09:05:00'));
        $thirdOccurrence = (new ChoreOccurrenceModel($db))->findByChoreAndDueAt((int) $choreId, '2026-04-05 09:00:00');
        $this->assertNotNull($thirdOccurrence);
        $this->assertSame($ownerId, (int) $thirdOccurrence['assigned_user_id']);
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

    private function attachMemberRole(BaseConnection $db, int $householdId, int $userId, int $assignedBy): int
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

        return (int) $membershipId;
    }

    private function executor(BaseConnection $db): ChoreRecurringExecutionService
    {
        return new ChoreRecurringExecutionService(
            db: $db,
            recurringRuleModel: new RecurringRuleModel($db),
            choreModel: new ChoreModel($db),
            choreOccurrenceModel: new ChoreOccurrenceModel($db),
            recurringScheduleService: new RecurringScheduleService(),
            choreOccurrenceService: new ChoreOccurrenceService(
                db: $db,
                householdAuthorizationService: new HouseholdAuthorizationService(
                    membershipModel: new HouseholdMembershipModel($db),
                    membershipRoleModel: new MembershipRoleModel($db),
                    rolePermissionModel: new RolePermissionModel($db),
                ),
                householdModel: new HouseholdModel($db),
                householdMembershipModel: new HouseholdMembershipModel($db),
                choreModel: new ChoreModel($db),
                choreOccurrenceModel: new ChoreOccurrenceModel($db),
                choreRotationService: new ChoreRotationService(),
                auditLogService: new AuditLogService(new AuditLogModel($db)),
            ),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );
    }
}
