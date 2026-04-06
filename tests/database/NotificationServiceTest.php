<?php

declare(strict_types=1);

use App\Database\Seeds\DatabaseSeeder;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Notifications\NotificationModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Models\Authorization\RoleModel;
use App\Models\Audit\AuditLogModel;
use App\Services\Audit\AuditLogService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class NotificationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreateReadAndMarkAllLifecycle(): void
    {
        $db = db_connect($this->DBGroup);
        $userId = $this->createUser($db, 'notify-service@example.test', 'Notify Service');
        $household = $this->provisionHousehold($db, $userId, 'Casa Notify');
        $service = new NotificationService($db, new NotificationModel($db), new UserModel($db), new UserPreferenceModel($db), new HouseholdModel($db));

        $service->createForUsers(
            [$userId],
            (int) $household['id'],
            'expense_created',
            'Nuova spesa registrata',
            'Supermercato',
            ['expense_id' => 10, 'household_slug' => (string) $household['slug']],
            null,
            false,
        );
        $service->createForUsers(
            [$userId],
            null,
            'invitation_received',
            'Invito ricevuto',
            'Sei stato invitato.',
            ['accept_url' => site_url('invitations/accept/test-token')],
            null,
            false,
        );

        $drawer = $service->drawerContext($userId, (int) $household['id'], (string) $household['slug']);
        $this->assertSame(2, $drawer['unreadCount']);
        $this->assertCount(2, $drawer['items']);

        $firstUnreadId = (int) $drawer['items'][0]['id'];
        $marked = $service->markAsRead($userId, $firstUnreadId);

        $this->assertNotNull($marked);
        $this->assertNotNull($marked['read_at']);

        $afterSingleRead = $service->drawerContext($userId, (int) $household['id'], (string) $household['slug']);
        $this->assertSame(1, $afterSingleRead['unreadCount']);

        $updatedRows = $service->markAllAsRead($userId, (int) $household['id'], true);
        $this->assertGreaterThanOrEqual(1, $updatedRows);

        $afterAllRead = $service->centerContext($userId, (int) $household['id'], (string) $household['slug']);
        $this->assertSame(0, $afterAllRead['unread_count']);
        $this->assertNotEmpty($afterAllRead['notifications'][0]['target_url']);
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
}
