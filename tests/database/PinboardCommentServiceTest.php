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
use App\Services\Pinboard\PinboardCommentService;
use App\Services\Pinboard\PinboardService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PinboardCommentServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    public function testCreateCommentPersistsAuditAndNotifiesPostAuthor(): void
    {
        $db = db_connect($this->DBGroup);
        $ownerId = $this->createUser($db, 'owner-pinboard-comment@example.test', 'Owner Comment');
        $memberId = $this->createUser($db, 'member-pinboard-comment@example.test', 'Member Comment');
        $household = $this->provisionHousehold($db, $ownerId, 'Casa Commenti');
        $this->attachMemberRole($db, (int) $household['id'], $memberId, $ownerId);

        $post = $this->pinboardService($db)->create($ownerId, (string) $household['slug'], [
            'title' => 'Lavatrice sabato',
            'body' => 'Ricordatevi di liberare il bagno entro le 10.',
            'post_type' => 'note',
            'is_pinned' => '0',
            'due_at' => '',
        ]);

        $comment = $this->commentService($db)->create($memberId, (string) $household['slug'], (int) $post['id'], 'Ricevuto, ci penso io.');

        $this->assertSame('Ricevuto, ci penso io.', $comment['body']);

        $storedComment = (new PinboardCommentModel($db))->findForPost((int) $post['id'], (int) $comment['id']);
        $this->assertNotNull($storedComment);

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))->listForEntity('pinboard_comment', (int) $comment['id'], (int) $household['id']),
        );

        $this->assertSame(['pinboard_comment.created'], $auditActions);

        $ownerNotifications = (new NotificationModel($db))
            ->where('user_id', $ownerId)
            ->where('household_id', (int) $household['id'])
            ->where('type', 'pinboard_comment_created')
            ->findAll();

        $memberNotifications = (new NotificationModel($db))
            ->where('user_id', $memberId)
            ->where('household_id', (int) $household['id'])
            ->where('type', 'pinboard_comment_created')
            ->findAll();

        $this->assertCount(1, $ownerNotifications);
        $this->assertCount(0, $memberNotifications);
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

    private function pinboardService(BaseConnection $db): PinboardService
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

    private function commentService(BaseConnection $db): PinboardCommentService
    {
        return new PinboardCommentService(
            db: $db,
            householdAuthorizationService: new HouseholdAuthorizationService(
                membershipModel: new HouseholdMembershipModel($db),
                membershipRoleModel: new MembershipRoleModel($db),
                rolePermissionModel: new RolePermissionModel($db),
            ),
            householdMembershipModel: new HouseholdMembershipModel($db),
            pinboardPostModel: new PinboardPostModel($db),
            pinboardCommentModel: new PinboardCommentModel($db),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
            notificationService: new NotificationService($db, new NotificationModel($db)),
        );
    }
}
