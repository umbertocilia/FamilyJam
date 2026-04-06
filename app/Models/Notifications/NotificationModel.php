<?php

declare(strict_types=1);

namespace App\Models\Notifications;

use App\Models\BaseModel;

final class NotificationModel extends BaseModel
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'user_id',
        'household_id',
        'type',
        'title',
        'body',
        'data_json',
        'read_at',
    ];

    /**
     * @param array{household_id?: int|null, include_global?: bool, unread_only?: bool} $filters
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, array $filters = [], int $limit = 50): array
    {
        $builder = $this->baseQuery();
        $builder->where('notifications.user_id', $userId)
            ->where('notifications.deleted_at', null);

        $householdId = $filters['household_id'] ?? null;
        $includeGlobal = (bool) ($filters['include_global'] ?? true);

        if ($householdId !== null) {
            if ($includeGlobal) {
                $builder->groupStart()
                    ->where('notifications.household_id', (int) $householdId)
                    ->orWhere('notifications.household_id', null)
                    ->groupEnd();
            } else {
                $builder->where('notifications.household_id', (int) $householdId);
            }
        }

        if (! empty($filters['unread_only'])) {
            $builder->where('notifications.read_at', null);
        }

        return $builder
            ->orderBy('notifications.read_at', 'ASC')
            ->orderBy('notifications.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForUser(int $userId, ?int $householdId = null, bool $includeGlobal = true, int $limit = 6): array
    {
        return $this->listForUser($userId, [
            'household_id' => $householdId,
            'include_global' => $includeGlobal,
            'unread_only' => true,
        ], $limit);
    }

    public function unreadCountForUser(int $userId, ?int $householdId = null, bool $includeGlobal = true): int
    {
        $builder = $this->builder()
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->where('read_at', null);

        if ($householdId !== null) {
            if ($includeGlobal) {
                $builder->groupStart()
                    ->where('household_id', $householdId)
                    ->orWhere('household_id', null)
                    ->groupEnd();
            } else {
                $builder->where('household_id', $householdId);
            }
        }

        return (int) $builder->countAllResults();
    }

    public function findForUser(int $userId, int $notificationId): ?array
    {
        $row = $this->baseQuery()
            ->where('notifications.user_id', $userId)
            ->where('notifications.id', $notificationId)
            ->where('notifications.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row === null || $row === [] ? null : $row;
    }

    public function markAsRead(int $userId, int $notificationId, string $readAt): bool
    {
        $this->builder()
            ->where('user_id', $userId)
            ->where('id', $notificationId)
            ->where('deleted_at', null)
            ->where('read_at', null)
            ->set([
                'read_at' => $readAt,
                'updated_at' => $readAt,
            ])
            ->update();

        return $this->db->affectedRows() > 0;
    }

    public function markAllAsReadForUser(int $userId, string $readAt, ?int $householdId = null, bool $includeGlobal = true): int
    {
        $builder = $this->builder()
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->where('read_at', null);

        if ($householdId !== null) {
            if ($includeGlobal) {
                $builder->groupStart()
                    ->where('household_id', $householdId)
                    ->orWhere('household_id', null)
                    ->groupEnd();
            } else {
                $builder->where('household_id', $householdId);
            }
        }

        $builder->set([
            'read_at' => $readAt,
            'updated_at' => $readAt,
        ])->update();

        return $this->db->affectedRows();
    }

    private function baseQuery()
    {
        return $this->builder()
            ->select([
                'notifications.*',
                'households.name AS household_name',
                'households.slug AS household_slug',
            ])
            ->join('households', 'households.id = notifications.household_id', 'left');
    }
}
