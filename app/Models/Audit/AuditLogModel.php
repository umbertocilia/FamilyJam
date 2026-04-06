<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Models\BaseModel;

final class AuditLogModel extends BaseModel
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'household_id',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'before_json',
        'after_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEntity(string $entityType, int $entityId, ?int $householdId = null): array
    {
        $builder = $this->select('audit_logs.*, users.display_name AS actor_name')
            ->join('users', 'users.id = audit_logs.actor_user_id', 'left')
            ->where('audit_logs.entity_type', $entityType)
            ->where('audit_logs.entity_id', $entityId);

        if ($householdId !== null) {
            $builder->where('audit_logs.household_id', $householdId);
        }

        return $builder->orderBy('audit_logs.created_at', 'DESC')->findAll();
    }
}
