<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\BaseModel;

final class ExpenseGroupMemberModel extends BaseModel
{
    protected $table = 'expense_group_members';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'expense_group_id',
        'user_id',
    ];

    /**
     * @param list<int> $groupIds
     * @return array<int, list<int>>
     */
    public function userIdsByGroupIds(array $groupIds): array
    {
        if ($groupIds === []) {
            return [];
        }

        $rows = $this->whereIn('expense_group_id', $groupIds)
            ->orderBy('expense_group_id', 'ASC')
            ->orderBy('user_id', 'ASC')
            ->findAll();

        $map = [];

        foreach ($rows as $row) {
            $groupId = (int) $row['expense_group_id'];
            $map[$groupId] ??= [];
            $map[$groupId][] = (int) $row['user_id'];
        }

        return $map;
    }

    /**
     * @param list<int> $userIds
     */
    public function replaceForGroup(int $groupId, array $userIds): void
    {
        $this->where('expense_group_id', $groupId)->delete();

        foreach (array_values(array_unique($userIds)) as $userId) {
            $this->insert([
                'expense_group_id' => $groupId,
                'user_id' => $userId,
            ]);
        }
    }
}
