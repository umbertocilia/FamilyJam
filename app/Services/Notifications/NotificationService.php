<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Households\HouseholdModel;
use App\Models\Notifications\NotificationModel;
use App\Services\Auth\OutboundEmailService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use JsonException;

final class NotificationService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?NotificationModel $notificationModel = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?OutboundEmailService $outboundEmailService = null,
    ) {
    }

    /**
     * @param list<int> $userIds
     * @param array<string, mixed>|null $data
     */
    public function createForUsers(array $userIds, ?int $householdId, string $type, string $title, ?string $body = null, ?array $data = null, ?int $actorUserId = null, bool $sendEmail = true): void
    {
        $db = $this->db ?? Database::connect();
        $notificationModel = $this->notificationModel ?? new NotificationModel($db);
        $userModel = $this->userModel ?? new UserModel($db);
        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($db);
        $household = $householdId === null ? null : ($this->householdModel ?? new HouseholdModel($db))->find($householdId);
        $outboundEmail = $this->outboundEmailService ?? service('outboundEmail');

        foreach (array_values(array_unique($userIds)) as $userId) {
            if ($actorUserId !== null && $userId === $actorUserId) {
                continue;
            }

            $encodedData = $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR);
            $notificationModel->insert([
                'user_id' => $userId,
                'household_id' => $householdId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data_json' => $encodedData,
                'read_at' => null,
            ]);

            $notificationId = (int) $notificationModel->getInsertID();
            $notification = $notificationModel->findForUser($userId, $notificationId);

            if ($notification === null) {
                continue;
            }

            $decorated = $this->decorateNotification($notification);
            $user = $userModel->find($userId);

            if (! $sendEmail || $user === null || ! $this->emailNotificationsEnabled($userPreferenceModel->findByUserId($userId))) {
                continue;
            }

            $outboundEmail->sendNotificationEmail($user, $decorated, $household);
        }
    }

    /**
     * @param array{accept_url?: string, household_slug?: string, invitation_id?: int}|null $data
     */
    public function notifyInvitationReceived(int $recipientUserId, ?int $householdId, string $householdName, string $roleName, ?array $data = null): void
    {
        helper('ui');
        $body = ui_text('notification.body.invitation_received', ['household' => $householdName, 'role' => $roleName]);
        $this->createForUsers([$recipientUserId], $householdId, 'invitation_received', ui_text('notification.title.invitation_received'), $body, $data, null, false);
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifyExpenseCreated(array $recipientIds, ?int $householdId, string $householdSlug, int $expenseId, string $title, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'expense_created',
            ui_text('notification.title.expense_created'),
            $title,
            ['expense_id' => $expenseId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifyExpenseUpdated(array $recipientIds, ?int $householdId, string $householdSlug, int $expenseId, string $title, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'expense_updated',
            ui_text('notification.title.expense_updated'),
            $title,
            ['expense_id' => $expenseId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifySettlementCreated(array $recipientIds, ?int $householdId, string $householdSlug, int $settlementId, string $amountLabel, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'settlement_created',
            ui_text('notification.title.settlement_created'),
            $amountLabel,
            ['settlement_id' => $settlementId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    public function notifyChoreAssigned(int $recipientUserId, ?int $householdId, string $householdSlug, int $occurrenceId, string $title, string $dueAt, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            [$recipientUserId],
            $householdId,
            'chore_assigned',
            ui_text('notification.title.chore_assigned'),
            ui_text('notification.body.chore_assigned', ['title' => $title, 'due_at' => $dueAt]),
            ['occurrence_id' => $occurrenceId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    public function notifyChoreDueSoon(int $recipientUserId, ?int $householdId, string $householdSlug, int $occurrenceId, string $title, string $dueAt): void
    {
        helper('ui');
        $this->createForUsers(
            [$recipientUserId],
            $householdId,
            'chore_due_soon',
            ui_text('notification.title.chore_due_soon'),
            ui_text('notification.body.chore_due_soon', ['title' => $title, 'due_at' => $dueAt]),
            ['occurrence_id' => $occurrenceId, 'household_slug' => $householdSlug],
        );
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifyChoreCompleted(array $recipientIds, ?int $householdId, string $householdSlug, int $occurrenceId, string $title, string $memberName, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'chore_completed',
            ui_text('notification.title.chore_completed'),
            ui_text('notification.body.chore_completed', ['member' => $memberName, 'title' => $title]),
            ['occurrence_id' => $occurrenceId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifyPinboardPostCreated(array $recipientIds, ?int $householdId, string $householdSlug, int $postId, string $title, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'pinboard_post_created',
            ui_text('notification.title.pinboard_post_created'),
            $title,
            ['post_id' => $postId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    /**
     * @param list<int> $recipientIds
     */
    public function notifyPinboardCommentCreated(array $recipientIds, ?int $householdId, string $householdSlug, int $postId, string $title, ?int $actorUserId = null): void
    {
        helper('ui');
        $this->createForUsers(
            $recipientIds,
            $householdId,
            'pinboard_comment_created',
            ui_text('notification.title.pinboard_comment_created'),
            $title,
            ['post_id' => $postId, 'household_slug' => $householdSlug],
            $actorUserId,
        );
    }

    /**
     * @param array{unread_only?: bool}|null $filters
     * @return array<string, mixed>
     */
    public function centerContext(int $userId, ?int $householdId = null, ?string $householdSlug = null, ?array $filters = null): array
    {
        $unreadOnly = (bool) (($filters['unread_only'] ?? false));
        $notifications = array_map(
            fn (array $row): array => $this->decorateNotification($row),
            ($this->notificationModel ?? new NotificationModel($this->db ?? Database::connect()))
                ->listForUser($userId, [
                    'household_id' => $householdId,
                    'include_global' => true,
                    'unread_only' => $unreadOnly,
                ], 100),
        );

        return [
            'scope' => [
                'household_id' => $householdId,
                'household_slug' => $householdSlug,
                'label' => $householdId === null ? ui_text('common.scope.account') : ui_text('common.scope.household_global'),
                'filter_url' => $householdSlug === null ? route_url('notifications.global') : route_url('notifications.index', $householdSlug),
                'unread_url' => ($householdSlug === null ? route_url('notifications.global') : route_url('notifications.index', $householdSlug)) . '?history=0',
            ],
            'notifications' => $notifications,
            'unread_count' => ($this->notificationModel ?? new NotificationModel($this->db ?? Database::connect()))
                ->unreadCountForUser($userId, $householdId, true),
            'mark_all_url' => $householdSlug === null ? route_url('notifications.read_all.global') : route_url('notifications.read_all', $householdSlug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function drawerContext(int $userId, ?int $householdId = null, ?string $householdSlug = null, int $limit = 6): array
    {
        $model = $this->notificationModel ?? new NotificationModel($this->db ?? Database::connect());
        $items = array_map(
            fn (array $row): array => $this->decorateNotification($row),
            $model->recentForUser($userId, $householdId, true, $limit),
        );

        return [
            'items' => $items,
            'unreadCount' => $model->unreadCountForUser($userId, $householdId, true),
            'centerUrl' => $householdSlug === null ? route_url('notifications.global') : route_url('notifications.index', $householdSlug),
            'markAllUrl' => $householdSlug === null ? route_url('notifications.read_all.global') : route_url('notifications.read_all', $householdSlug),
            'pollUrl' => $householdSlug === null ? route_url('notifications.poll.global') : route_url('notifications.poll', $householdSlug),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function markAsRead(int $userId, int $notificationId): ?array
    {
        $model = $this->notificationModel ?? new NotificationModel($this->db ?? Database::connect());
        $readAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $model->markAsRead($userId, $notificationId, $readAt);
        $notification = $model->findForUser($userId, $notificationId);

        return $notification === null ? null : $this->decorateNotification($notification);
    }

    public function markAllAsRead(int $userId, ?int $householdId = null, bool $includeGlobal = true): int
    {
        return ($this->notificationModel ?? new NotificationModel($this->db ?? Database::connect()))
            ->markAllAsReadForUser($userId, (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'), $householdId, $includeGlobal);
    }

    /**
     * @param array<string, mixed> $notification
     * @return array<string, mixed>
     */
    private function decorateNotification(array $notification): array
    {
        $data = [];

        if (! empty($notification['data_json'])) {
            try {
                $data = (array) json_decode((string) $notification['data_json'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $data = [];
            }
        }

        $notification['data'] = $data;
        $notification['target_url'] = $this->targetUrl((string) ($notification['type'] ?? ''), $data, $notification);

        return $notification;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $notification
     */
    private function targetUrl(string $type, array $data, array $notification): ?string
    {
        $householdSlug = is_string($data['household_slug'] ?? null) && trim((string) $data['household_slug']) !== ''
            ? (string) $data['household_slug']
            : (is_string($notification['household_slug'] ?? null) && trim((string) $notification['household_slug']) !== '' ? (string) $notification['household_slug'] : null);

        return match ($type) {
            'invitation_received' => is_string($data['accept_url'] ?? null) ? (string) $data['accept_url'] : route_url('households.index'),
            'expense_created', 'expense_updated' => $householdSlug !== null && ! empty($data['expense_id'])
                ? route_url('expenses.show', $householdSlug, (string) $data['expense_id'])
                : null,
            'settlement_created' => $householdSlug !== null ? route_url('settlements.index', $householdSlug) : null,
            'chore_assigned', 'chore_due_soon' => $householdSlug !== null ? route_url('chores.my', $householdSlug) : null,
            'pinboard_post_created', 'pinboard_comment_created' => $householdSlug !== null && ! empty($data['post_id'])
                ? route_url('pinboard.show', $householdSlug, (string) $data['post_id'])
                : null,
            default => null,
        };
    }

    /**
     * @param array<string, mixed>|null $preferences
     */
    private function emailNotificationsEnabled(?array $preferences): bool
    {
        if ($preferences === null || empty($preferences['notification_preferences_json'])) {
            return true;
        }

        try {
            $payload = (array) json_decode((string) $preferences['notification_preferences_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return true;
        }

        return (bool) ($payload['email_notifications'] ?? true);
    }
}
