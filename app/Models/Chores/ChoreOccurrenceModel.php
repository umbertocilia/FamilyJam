<?php

declare(strict_types=1);

namespace App\Models\Chores;

use App\Models\BaseModel;

final class ChoreOccurrenceModel extends BaseModel
{
    protected $table = 'chore_occurrences';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'household_id',
        'chore_id',
        'assigned_user_id',
        'due_at',
        'completed_at',
        'completed_by',
        'skipped_at',
        'skipped_by',
        'skip_reason',
        'status',
        'points_awarded',
        'reminder_sent_at',
    ];

    /**
     * @param array{status?: string|null, assigned_user_id?: int|string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId, array $filters = []): array
    {
        $builder = $this->baseDetailQuery()
            ->where('chore_occurrences.household_id', $householdId);

        if (($filters['status'] ?? null) !== null && (string) $filters['status'] !== '') {
            $builder->where('chore_occurrences.status', (string) $filters['status']);
        }

        if (($filters['assigned_user_id'] ?? null) !== null && (string) $filters['assigned_user_id'] !== '') {
            $builder->where('chore_occurrences.assigned_user_id', (int) $filters['assigned_user_id']);
        }

        return $builder
            ->orderBy('chore_occurrences.due_at', 'ASC')
            ->orderBy('chore_occurrences.id', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForChore(int $householdId, int $choreId, int $limit = 100): array
    {
        return $this->baseDetailQuery()
            ->where('chore_occurrences.household_id', $householdId)
            ->where('chore_occurrences.chore_id', $choreId)
            ->orderBy('chore_occurrences.due_at', 'DESC')
            ->orderBy('chore_occurrences.id', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $householdId, int $userId, int $limit = 100): array
    {
        return $this->baseDetailQuery()
            ->where('chore_occurrences.household_id', $householdId)
            ->where('chore_occurrences.assigned_user_id', $userId)
            ->orderBy('FIELD(chore_occurrences.status, "overdue", "pending", "completed", "skipped")', '', false)
            ->orderBy('chore_occurrences.due_at', 'ASC')
            ->orderBy('chore_occurrences.id', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $occurrenceId): ?array
    {
        return $this->baseDetailQuery()
            ->where('chore_occurrences.household_id', $householdId)
            ->where('chore_occurrences.id', $occurrenceId)
            ->first();
    }

    public function findByChoreAndDueAt(int $choreId, string $dueAt): ?array
    {
        return $this->where('chore_id', $choreId)
            ->where('due_at', $dueAt)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentAssignedForChore(int $choreId, int $limit = 50): array
    {
        return $this->select('assigned_user_id, due_at, status')
            ->where('chore_id', $choreId)
            ->where('assigned_user_id IS NOT NULL', null, false)
            ->orderBy('due_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAgenda(int $householdId, string $from, string $to): array
    {
        return $this->baseDetailQuery()
            ->where('chore_occurrences.household_id', $householdId)
            ->where('chore_occurrences.due_at >=', $from)
            ->where('chore_occurrences.due_at <=', $to)
            ->orderBy('chore_occurrences.due_at', 'ASC')
            ->orderBy('chore_occurrences.id', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listReminderCandidates(string $dueBefore, int $limit = 100): array
    {
        return $this->baseDetailQuery()
            ->groupStart()
                ->where('chore_occurrences.status', 'pending')
                ->orWhere('chore_occurrences.status', 'overdue')
            ->groupEnd()
            ->where('chore_occurrences.due_at <=', $dueBefore)
            ->where('chore_occurrences.reminder_sent_at', null)
            ->orderBy('chore_occurrences.due_at', 'ASC')
            ->orderBy('chore_occurrences.id', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function syncOverdueStatuses(string $now, ?int $householdId = null): int
    {
        $builder = $this->builder()
            ->where('due_at <', $now)
            ->where('status', 'pending');

        if ($householdId !== null) {
            $builder->where('household_id', $householdId);
        }

        $builder->set([
            'status' => 'overdue',
            'updated_at' => $now,
        ]);
        $builder->update();

        return $this->db->affectedRows();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fairnessRows(int $householdId): array
    {
        return $this->select([
                'users.id AS user_id',
                'users.display_name',
                'users.email',
                'SUM(CASE WHEN chore_occurrences.status = "completed" THEN 1 ELSE 0 END) AS completed_count',
                'SUM(CASE WHEN chore_occurrences.status = "skipped" THEN 1 ELSE 0 END) AS skipped_count',
                'SUM(CASE WHEN chore_occurrences.status = "pending" THEN 1 ELSE 0 END) AS pending_count',
                'SUM(CASE WHEN chore_occurrences.status = "overdue" THEN 1 ELSE 0 END) AS overdue_count',
                'SUM(CASE WHEN chore_occurrences.status = "completed" THEN chore_occurrences.points_awarded ELSE 0 END) AS points_total',
                'SUM(CASE WHEN chore_occurrences.status = "completed" THEN chores.estimated_minutes ELSE 0 END) AS completed_minutes',
            ])
            ->join('users', 'users.id = chore_occurrences.assigned_user_id', 'inner')
            ->join('chores', 'chores.id = chore_occurrences.chore_id', 'inner')
            ->where('chore_occurrences.household_id', $householdId)
            ->groupBy('users.id')
            ->orderBy('points_total', 'DESC')
            ->orderBy('completed_count', 'DESC')
            ->findAll();
    }

    private function baseDetailQuery()
    {
        return $this->select([
                'chore_occurrences.*',
                'chores.title AS chore_title',
                'chores.description AS chore_description',
                'chores.assignment_mode',
                'chores.points AS chore_points',
                'chores.estimated_minutes',
                'assigned.display_name AS assigned_user_name',
                'completed_user.display_name AS completed_by_name',
                'skipped_user.display_name AS skipped_by_name',
            ])
            ->join('chores', 'chores.id = chore_occurrences.chore_id', 'inner')
            ->join('users AS assigned', 'assigned.id = chore_occurrences.assigned_user_id', 'left')
            ->join('users AS completed_user', 'completed_user.id = chore_occurrences.completed_by', 'left')
            ->join('users AS skipped_user', 'skipped_user.id = chore_occurrences.skipped_by', 'left');
    }
}
