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
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Finance\SettlementModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\InvitationModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Notifications\NotificationModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Balances\BalanceService;
use App\Services\Balances\SettlementService;
use App\Services\Chores\ChoreOccurrenceService;
use App\Services\Chores\ChoreReminderService;
use App\Services\Chores\ChoreRotationService;
use App\Services\Expenses\ExpenseService;
use App\Services\Expenses\ExpenseValidationService;
use App\Services\Expenses\SplitCalculationService;
use App\Services\Households\HouseholdContextService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Households\InvitationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class NotificationTriggerIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testDomainServicesEmitNotificationsForMainEvents(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-trigger@example.test', 'Owner Trigger');
        $memberId = $this->createUser($db, 'member-trigger@example.test', 'Member Trigger');
        $inviteeId = $this->createUser($db, 'invitee-trigger@example.test', 'Invitee Trigger');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Trigger');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $authorization = $this->authorization($db);
        $notificationService = new NotificationService($db, new NotificationModel($db), new UserModel($db), new UserPreferenceModel($db), new HouseholdModel($db));

        $invitationService = new InvitationService(
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
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            notificationService: $notificationService,
        );

        $invitationService->create($ownerId, (string) $household['slug'], [
            'email' => 'invitee-trigger@example.test',
            'role_code' => 'member',
        ]);

        $expenseService = new ExpenseService(
            db: $db,
            householdAuthorizationService: $authorization,
            expenseValidationService: new ExpenseValidationService(new SplitCalculationService()),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            auditLogModel: new AuditLogModel($db),
            notificationService: $notificationService,
        );

        $expense = $expenseService->create($ownerId, (string) $household['slug'], [
            'title' => 'Spesa comune',
            'description' => null,
            'expense_date' => '2026-04-02',
            'currency' => 'EUR',
            'total_amount' => '30.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '30.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $expenseService->update($ownerId, (string) $household['slug'], (int) $expense['id'], [
            'title' => 'Spesa comune aggiornata',
            'description' => null,
            'expense_date' => '2026-04-02',
            'currency' => 'EUR',
            'total_amount' => '32.00',
            'category_id' => '',
            'split_method' => 'equal',
            'payers' => [
                $ownerId => ['enabled' => '1', 'amount' => '32.00'],
            ],
            'splits' => [
                $ownerId => ['enabled' => '1'],
                $memberId => ['enabled' => '1'],
            ],
        ]);

        $balanceService = new BalanceService(
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            expenseModel: new ExpenseModel($db),
            expensePayerModel: new ExpensePayerModel($db),
            expenseSplitModel: new ExpenseSplitModel($db),
            settlementModel: new SettlementModel($db),
        );

        $settlementService = new SettlementService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            settlementModel: new SettlementModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            balanceService: $balanceService,
            notificationService: $notificationService,
        );

        $settlementService->create($ownerId, (string) $household['slug'], [
            'from_user_id' => (string) $memberId,
            'to_user_id' => (string) $ownerId,
            'settlement_date' => '2026-04-03',
            'currency' => 'EUR',
            'amount' => '5.00',
            'payment_method' => 'cash',
            'note' => null,
        ]);

        $choreId = (int) (new ChoreModel($db))->insert([
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

        $occurrenceService = new ChoreOccurrenceService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            choreModel: new ChoreModel($db),
            choreOccurrenceModel: new ChoreOccurrenceModel($db),
            choreRotationService: new ChoreRotationService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            notificationService: $notificationService,
        );

        $occurrence = $occurrenceService->createOccurrenceForChore($ownerId, (string) $household['slug'], $choreId, '2026-04-03 09:00:00');

        $reminderService = new ChoreReminderService(
            db: $db,
            choreOccurrenceModel: new ChoreOccurrenceModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            householdModel: new HouseholdModel($db),
            notificationService: $notificationService,
        );

        $reminderService->run(new DateTimeImmutable('2026-04-02 10:00:00'), 24, 50);

        $inviteeTypes = $this->notificationTypesForUser($db, $inviteeId);
        $memberTypes = $this->notificationTypesForUser($db, $memberId);

        $this->assertContains('invitation_received', $inviteeTypes);
        $this->assertContains('expense_created', $memberTypes);
        $this->assertContains('expense_updated', $memberTypes);
        $this->assertContains('settlement_created', $memberTypes);
        $this->assertContains('chore_assigned', $memberTypes);
        $this->assertContains('chore_due_soon', $memberTypes);

        $assignmentNotification = (new NotificationModel($db))
            ->where('user_id', $memberId)
            ->where('type', 'chore_assigned')
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($assignmentNotification);
        $this->assertStringContainsString((string) $occurrence['id'], (string) $assignmentNotification['data_json']);
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

    private function authorization(BaseConnection $db): HouseholdAuthorizationService
    {
        return new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );
    }

    /**
     * @return list<string>
     */
    private function notificationTypesForUser(BaseConnection $db, int $userId): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['type'],
            (new NotificationModel($db))->where('user_id', $userId)->findAll(),
        );
    }
}
