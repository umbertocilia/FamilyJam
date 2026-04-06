<?php

declare(strict_types=1);

use App\Authorization\SystemRole;
use App\Database\Seeds\DatabaseSeeder;
use App\Models\Attachments\AttachmentModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Notifications\NotificationModel;
use App\Models\Pinboard\PinboardCommentModel;
use App\Models\Pinboard\PinboardPostModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Attachments\AttachmentStorageService;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Notifications\NotificationService;
use App\Services\Pinboard\PinboardService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PinboardServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreateUpdatePinAndDeletePostPersistsAuditAndNotifications(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-pinboard@example.test', 'Owner Pinboard');
        $memberId = $this->createUser($db, 'member-pinboard@example.test', 'Member Pinboard');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Pinboard');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $service = $this->service($db);

        $created = $service->create($ownerId, (string) $household['slug'], [
            'title' => 'Riunione spese mensili',
            'body' => 'Ricordiamoci di chiudere i conti entro domenica sera.',
            'post_type' => 'announcement',
            'is_pinned' => '1',
            'due_at' => '2026-04-10T20:30',
        ]);

        $this->assertSame('Riunione spese mensili', $created['title']);
        $this->assertSame(1, (int) $created['is_pinned']);

        $updated = $service->update($ownerId, (string) $household['slug'], (int) $created['id'], [
            'title' => 'Riunione spese e bollette',
            'body' => 'Ricordiamoci di chiudere i conti e la bolletta internet.',
            'post_type' => 'todo',
            'is_pinned' => '0',
            'due_at' => '2026-04-11T09:00',
        ]);

        $this->assertSame('Riunione spese e bollette', $updated['title']);
        $this->assertSame('todo', $updated['post_type']);
        $this->assertSame(0, (int) $updated['is_pinned']);

        $toggled = $service->togglePin($ownerId, (string) $household['slug'], (int) $created['id']);
        $this->assertSame(1, (int) $toggled['is_pinned']);

        $service->softDelete($ownerId, (string) $household['slug'], (int) $created['id']);

        $deleted = (new PinboardPostModel($db))->findDetailForHousehold((int) $household['id'], (int) $created['id'], true);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted['deleted_at']);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('pinboard_post', (int) $created['id'], (int) $household['id']),
        );

        $this->assertSame([
            'pinboard_post.deleted',
            'pinboard_post.pinned',
            'pinboard_post.updated',
            'pinboard_post.created',
        ], $auditActions);

        $notifications = (new NotificationModel($db))
            ->where('user_id', $memberId)
            ->where('household_id', (int) $household['id'])
            ->where('type', 'pinboard_post_created')
            ->findAll();

        $this->assertCount(1, $notifications);
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

    private function service(BaseConnection $db): PinboardService
    {
        $authorization = new HouseholdAuthorizationService(
            membershipModel: new HouseholdMembershipModel($db),
            membershipRoleModel: new MembershipRoleModel($db),
            rolePermissionModel: new RolePermissionModel($db),
        );

        return new PinboardService(
            db: $db,
            householdAuthorizationService: $authorization,
            householdModel: new HouseholdModel($db),
            householdMembershipModel: new HouseholdMembershipModel($db),
            pinboardPostModel: new PinboardPostModel($db),
            pinboardCommentModel: new PinboardCommentModel($db),
            attachmentModel: new AttachmentModel($db),
            attachmentStorageService: new AttachmentStorageService(new AttachmentModel($db)),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            notificationService: new NotificationService($db, new NotificationModel($db)),
        );
    }
}
