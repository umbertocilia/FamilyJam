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
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Chores\ChoreOccurrenceService;
use App\Services\Chores\ChoreRotationService;
use App\Services\Households\HouseholdProvisioningService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class ChoreOccurrenceServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCompleteAndSkipLifecycleUpdatesOccurrenceAndAudit(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-chore-occ@example.test', 'Owner Chore');
        $memberId = $this->createUser($db, 'member-chore-occ@example.test', 'Member Chore');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Chores', true);
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $choreId = (new ChoreModel($db))->insert([
            'household_id' => (int) $household['id'],
            'recurring_rule_id' => null,
            'title' => 'Bagno',
            'description' => 'Pulizia bagno',
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $memberId,
            'rotation_anchor_user_id' => null,
            'points' => 5,
            'estimated_minutes' => 20,
            'is_active' => 1,
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $occurrenceId = (new ChoreOccurrenceModel($db))->insert([
            'household_id' => (int) $household['id'],
            'chore_id' => (int) $choreId,
            'assigned_user_id' => $memberId,
            'due_at' => '2026-04-02 09:00:00',
            'status' => 'pending',
            'points_awarded' => 0,
        ], true);

        $service = $this->occurrenceService($db);
        $completed = $service->complete($memberId, (string) $household['slug'], (int) $occurrenceId);

        $this->assertSame('completed', $completed['status']);
        $this->assertSame(5, (int) $completed['points_awarded']);

        $secondOccurrenceId = (new ChoreOccurrenceModel($db))->insert([
            'household_id' => (int) $household['id'],
            'chore_id' => (int) $choreId,
            'assigned_user_id' => $memberId,
            'due_at' => '2026-04-03 09:00:00',
            'status' => 'pending',
            'points_awarded' => 0,
        ], true);

        $skipped = $service->skip($memberId, (string) $household['slug'], (int) $secondOccurrenceId, 'Fuori casa');

        $this->assertSame('skipped', $skipped['status']);
        $this->assertSame('Fuori casa', $skipped['skip_reason']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('chore_occurrence', (int) $secondOccurrenceId, (int) $household['id']),
        );

        $this->assertSame(['chore_occurrence.skipped'], $auditActions);
    }

    public function testSyncOverdueStatusesPromotesPastDuePendingOccurrences(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-chore-overdue@example.test', 'Owner Overdue');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Overdue', true);

        $choreId = (new ChoreModel($db))->insert([
            'household_id' => (int) $household['id'],
            'recurring_rule_id' => null,
            'title' => 'Cucina',
            'description' => null,
            'assignment_mode' => 'fixed',
            'fixed_assignee_user_id' => $ownerId,
            'rotation_anchor_user_id' => null,
            'points' => 3,
            'estimated_minutes' => 15,
            'is_active' => 1,
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
        ], true);

        $occurrenceId = (new ChoreOccurrenceModel($db))->insert([
            'household_id' => (int) $household['id'],
            'chore_id' => (int) $choreId,
            'assigned_user_id' => $ownerId,
            'due_at' => '2026-04-01 08:00:00',
            'status' => 'pending',
            'points_awarded' => 0,
        ], true);

        $updatedRows = $this->occurrenceService($db)->syncOverdueStatuses((int) $household['id'], new DateTimeImmutable('2026-04-02 10:00:00'));

        $this->assertSame(1, $updatedRows);
        $occurrence = (new ChoreOccurrenceModel($db))->find((int) $occurrenceId);
        $this->assertNotNull($occurrence);
        $this->assertSame('overdue', $occurrence['status']);
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
    private function provisionHousehold(BaseConnection $db, int $ownerId, string $name, bool $choreScoringEnabled): array
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
            'chore_scoring_enabled' => $choreScoringEnabled,
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

    private function occurrenceService(BaseConnection $db): ChoreOccurrenceService
    {
        return new ChoreOccurrenceService(
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
        );
    }
}
