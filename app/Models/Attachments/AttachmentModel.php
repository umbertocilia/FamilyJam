<?php

declare(strict_types=1);

namespace App\Models\Attachments;

use App\Models\TenantScopedModel;

final class AttachmentModel extends TenantScopedModel
{
    protected $table = 'attachments';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'uploaded_by',
        'entity_type',
        'entity_id',
        'original_name',
        'stored_name',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'checksum_sha256',
    ];

    public function findForHousehold(int $householdId, int $attachmentId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('id', $attachmentId)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEntity(int $householdId, string $entityType, int $entityId): array
    {
        return $this->where('household_id', $householdId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('deleted_at', null)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
