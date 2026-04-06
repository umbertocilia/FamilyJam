<?php

declare(strict_types=1);

namespace App\Services\Pinboard;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Pinboard\PinboardCommentModel;
use App\Models\Pinboard\PinboardPostModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DomainException;

final class PinboardCommentService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?PinboardPostModel $pinboardPostModel = null,
        private readonly ?PinboardCommentModel $pinboardCommentModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $userId, string $identifier, int $postId, string $body): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($userId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($userId, $identifier, Permission::MANAGE_PINBOARD)) {
            throw new DomainException('Non hai i permessi necessari per commentare questo post.');
        }

        $post = ($this->pinboardPostModel ?? new PinboardPostModel())->findDetailForHousehold((int) $membership['household_id'], $postId);

        if ($post === null) {
            throw new DomainException('Post pinboard non trovato.');
        }

        $resolvedBody = trim($body);

        if ($resolvedBody === '' || strlen($resolvedBody) < 2) {
            throw new DomainException('Il commento deve avere almeno 2 caratteri.');
        }

        $db = $this->db ?? Database::connect();
        $commentModel = $this->pinboardCommentModel ?? new PinboardCommentModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $notificationService = $this->notificationService ?? new NotificationService($db);
        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment((int) $membership['household_id']);

        $db->transException(true)->transStart();
        $commentId = $commentModel->insert([
            'post_id' => $postId,
            'author_user_id' => $userId,
            'body' => $resolvedBody,
        ], true);
        $after = $commentModel->findForPost($postId, (int) $commentId) ?? [];
        $audit->record(
            action: 'pinboard_comment.created',
            entityType: 'pinboard_comment',
            entityId: (int) $commentId,
            actorUserId: $userId,
            householdId: (int) $membership['household_id'],
            after: $after,
        );

        $recipientIds = [];
        foreach ($members as $member) {
            $memberUserId = (int) $member['user_id'];

            if ($memberUserId === $userId) {
                continue;
            }

            if ($memberUserId === (int) ($post['author_user_id'] ?? 0) || ! in_array($memberUserId, $recipientIds, true)) {
                $recipientIds[] = $memberUserId;
            }
        }

        $notificationService->notifyPinboardCommentCreated(
            $recipientIds,
            (int) $membership['household_id'],
            (string) ($membership['household_slug'] ?? ''),
            $postId,
            (string) $post['title'],
            $userId,
        );
        $db->transComplete();

        return $after;
    }
}
