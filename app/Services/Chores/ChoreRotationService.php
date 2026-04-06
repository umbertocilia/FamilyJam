<?php

declare(strict_types=1);

namespace App\Services\Chores;

final class ChoreRotationService
{
    /**
     * @param list<array<string, mixed>> $activeMembers
     */
    public function nextAssigneeUserId(array $activeMembers, ?int $anchorUserId, ?int $lastAssignedUserId): ?int
    {
        $orderedUserIds = array_values(array_map(
            static fn (array $member): int => (int) $member['user_id'],
            $activeMembers,
        ));

        if ($orderedUserIds === []) {
            return null;
        }

        if ($lastAssignedUserId !== null) {
            $lastIndex = array_search($lastAssignedUserId, $orderedUserIds, true);

            if ($lastIndex !== false) {
                return $orderedUserIds[($lastIndex + 1) % count($orderedUserIds)];
            }
        }

        if ($anchorUserId !== null) {
            $anchorIndex = array_search($anchorUserId, $orderedUserIds, true);

            if ($anchorIndex !== false) {
                return $orderedUserIds[$anchorIndex];
            }
        }

        return $orderedUserIds[0];
    }
}
