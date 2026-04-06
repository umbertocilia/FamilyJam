<?php

declare(strict_types=1);

namespace App\Services\Pinboard;

use App\Authorization\Permission;
use App\Models\Attachments\AttachmentModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Pinboard\PinboardCommentModel;
use App\Models\Pinboard\PinboardPostModel;
use App\Services\Attachments\AttachmentStorageService;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DomainException;

final class PinboardService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?PinboardPostModel $pinboardPostModel = null,
        private readonly ?PinboardCommentModel $pinboardCommentModel = null,
        private readonly ?AttachmentModel $attachmentModel = null,
        private readonly ?AttachmentStorageService $attachmentStorageService = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function indexContext(int $userId, string $identifier): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $posts = ($this->pinboardPostModel ?? new PinboardPostModel())->listForHousehold((int) $context['household']['id']);

        return array_merge($context, [
            'posts' => $posts,
            'summary' => [
                'posts' => count($posts),
                'pinned' => count(array_filter($posts, static fn (array $post): bool => (int) ($post['is_pinned'] ?? 0) === 1)),
                'due' => count(array_filter($posts, static fn (array $post): bool => ! empty($post['due_at']))),
                'comments' => array_sum(array_map(static fn (array $post): int => (int) ($post['comments_count'] ?? 0), $posts)),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailContext(int $userId, string $identifier, int $postId): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $post = ($this->pinboardPostModel ?? new PinboardPostModel())
            ->findDetailForHousehold((int) $context['household']['id'], $postId);

        if ($post === null) {
            return null;
        }

        return array_merge($context, [
            'post' => $post,
            'comments' => ($this->pinboardCommentModel ?? new PinboardCommentModel())->listForPost($postId),
            'attachments' => ($this->attachmentModel ?? new AttachmentModel())->listForEntity((int) $context['household']['id'], 'pinboard_post', $postId),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formContext(int $userId, string $identifier, ?int $postId = null): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManagePinboard']) {
            return null;
        }

        $post = null;
        $attachments = [];

        if ($postId !== null) {
            $post = ($this->pinboardPostModel ?? new PinboardPostModel())
                ->findDetailForHousehold((int) $context['household']['id'], $postId);

            if ($post === null) {
                return null;
            }

            $attachments = ($this->attachmentModel ?? new AttachmentModel())->listForEntity((int) $context['household']['id'], 'pinboard_post', $postId);
        }

        return array_merge($context, [
            'post' => $post,
            'attachments' => $attachments,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $userId, string $identifier, array $payload, ?UploadedFile $attachment = null): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManagePinboard']) {
            throw new DomainException('Non hai i permessi necessari per creare un post in bacheca.');
        }

        $normalized = $this->normalizePayload($payload);
        $db = $this->db ?? Database::connect();
        $postModel = $this->pinboardPostModel ?? new PinboardPostModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $attachmentStorage = $this->attachmentStorageService ?? new AttachmentStorageService();
        $notificationService = $this->notificationService ?? new NotificationService($db);

        $db->transException(true)->transStart();
        $postId = $postModel->insert([
            'household_id' => (int) $context['household']['id'],
            'author_user_id' => $userId,
            'title' => $normalized['title'],
            'body' => $normalized['body'],
            'post_type' => $normalized['post_type'],
            'is_pinned' => $normalized['is_pinned'],
            'due_at' => $normalized['due_at'],
        ], true);

        $storedAttachment = $attachmentStorage->storePinboardPostAttachment($attachment, (int) $context['household']['id'], $userId, (int) $postId);

        if ($storedAttachment !== null) {
            $attachmentStorage->bindToPinboardPost((int) $storedAttachment['id'], (int) $postId);
        }

        $after = $postModel->findDetailForHousehold((int) $context['household']['id'], (int) $postId) ?? [];
        $audit->record(
            action: 'pinboard_post.created',
            entityType: 'pinboard_post',
            entityId: (int) $postId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            after: $after,
        );

        $notificationService->notifyPinboardPostCreated(
            $this->notificationRecipients($context['members'], $userId),
            (int) $context['household']['id'],
            (string) ($context['membership']['household_slug'] ?? ''),
            (int) $postId,
            $normalized['title'],
            $userId,
        );

        $db->transComplete();

        return $after;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $userId, string $identifier, int $postId, array $payload, ?UploadedFile $attachment = null): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManagePinboard']) {
            throw new DomainException('Non hai i permessi necessari per modificare questo post.');
        }

        $db = $this->db ?? Database::connect();
        $postModel = $this->pinboardPostModel ?? new PinboardPostModel($db);
        $existing = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId);

        if ($existing === null) {
            throw new DomainException('Post pinboard non trovato.');
        }

        $normalized = $this->normalizePayload($payload);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $attachmentStorage = $this->attachmentStorageService ?? new AttachmentStorageService();

        $db->transException(true)->transStart();
        $postModel->update($postId, [
            'title' => $normalized['title'],
            'body' => $normalized['body'],
            'post_type' => $normalized['post_type'],
            'is_pinned' => $normalized['is_pinned'],
            'due_at' => $normalized['due_at'],
        ]);

        $storedAttachment = $attachmentStorage->storePinboardPostAttachment($attachment, (int) $context['household']['id'], $userId, $postId);

        if ($storedAttachment !== null) {
            $attachmentStorage->bindToPinboardPost((int) $storedAttachment['id'], $postId);
        }

        $after = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId) ?? [];
        $audit->record(
            action: 'pinboard_post.updated',
            entityType: 'pinboard_post',
            entityId: $postId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    /**
     * @return array<string, mixed>
     */
    public function togglePin(int $userId, string $identifier, int $postId): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManagePinboard']) {
            throw new DomainException('Non hai i permessi necessari per aggiornare questo post.');
        }

        $db = $this->db ?? Database::connect();
        $postModel = $this->pinboardPostModel ?? new PinboardPostModel($db);
        $existing = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId);

        if ($existing === null) {
            throw new DomainException('Post pinboard non trovato.');
        }

        $nextPinned = (int) ($existing['is_pinned'] ?? 0) === 1 ? 0 : 1;
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $postModel->update($postId, ['is_pinned' => $nextPinned]);
        $after = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId) ?? [];
        $audit->record(
            action: $nextPinned === 1 ? 'pinboard_post.pinned' : 'pinboard_post.unpinned',
            entityType: 'pinboard_post',
            entityId: $postId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    public function softDelete(int $userId, string $identifier, int $postId): void
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManagePinboard']) {
            throw new DomainException('Non hai i permessi necessari per eliminare questo post.');
        }

        $db = $this->db ?? Database::connect();
        $postModel = $this->pinboardPostModel ?? new PinboardPostModel($db);
        $commentModel = $this->pinboardCommentModel ?? new PinboardCommentModel($db);
        $existing = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId);

        if ($existing === null) {
            throw new DomainException('Post pinboard non trovato.');
        }

        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $attachmentModel = $this->attachmentModel ?? new AttachmentModel($db);

        $db->transException(true)->transStart();
        foreach ($commentModel->listForPost($postId) as $comment) {
            $commentModel->delete((int) $comment['id']);
        }
        foreach ($attachmentModel->listForEntity((int) $context['household']['id'], 'pinboard_post', $postId) as $attachment) {
            $attachmentModel->delete((int) $attachment['id']);
        }
        $postModel->delete($postId);
        $after = $postModel->findDetailForHousehold((int) $context['household']['id'], $postId, true) ?? [];
        $audit->record(
            action: 'pinboard_post.deleted',
            entityType: 'pinboard_post',
            entityId: $postId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();
    }

    /**
     * @return array{post: array<string, mixed>, attachment: array<string, mixed>}|null
     */
    public function attachmentContext(int $userId, string $identifier, int $postId, int $attachmentId): ?array
    {
        $detail = $this->detailContext($userId, $identifier, $postId);

        if ($detail === null) {
            return null;
        }

        $attachment = ($this->attachmentModel ?? new AttachmentModel())
            ->findForHousehold((int) $detail['membership']['household_id'], $attachmentId);

        if ($attachment === null || (string) ($attachment['entity_type'] ?? '') !== 'pinboard_post' || (int) ($attachment['entity_id'] ?? 0) !== $postId) {
            return null;
        }

        return [
            'post' => $detail['post'],
            'attachment' => $attachment,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $postType = strtolower(trim((string) ($payload['post_type'] ?? 'note')));
        $dueAt = trim((string) ($payload['due_at'] ?? ''));

        if ($title === '' || strlen($title) < 3 || strlen($title) > 160) {
            throw new DomainException('Il titolo del post deve avere tra 3 e 160 caratteri.');
        }

        if ($body === '' || strlen($body) < 3) {
            throw new DomainException('Il contenuto del post deve avere almeno 3 caratteri.');
        }

        if (! in_array($postType, ['note', 'announcement', 'todo'], true)) {
            throw new DomainException('Il tipo di post selezionato non e valido.');
        }

        $resolvedDueAt = null;

        if ($dueAt !== '') {
            try {
                $resolvedDueAt = (new \DateTimeImmutable($dueAt))->format('Y-m-d H:i:s');
            } catch (\Exception) {
                throw new DomainException('La data di scadenza del post non e valida.');
            }
        }

        return [
            'title' => $title,
            'body' => $body,
            'post_type' => $postType,
            'is_pinned' => $this->isTruthy($payload['is_pinned'] ?? null) ? 1 : 0,
            'due_at' => $resolvedDueAt,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $userId, string $identifier): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment((int) $household['id']);

        return [
            'membership' => $membership,
            'household' => $household,
            'members' => $members,
            'canManagePinboard' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($userId, $identifier, Permission::MANAGE_PINBOARD),
        ];
    }

    /**
     * @param list<array<string, mixed>> $members
     * @return list<int>
     */
    private function notificationRecipients(array $members, int $actorUserId): array
    {
        return array_values(array_filter(
            array_map(static fn (array $member): int => (int) $member['user_id'], $members),
            static fn (int $userId): bool => $userId !== $actorUserId,
        ));
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return is_string($value) && in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }
}
