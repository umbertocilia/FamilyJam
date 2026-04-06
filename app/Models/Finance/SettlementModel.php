<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\TenantScopedModel;

final class SettlementModel extends TenantScopedModel
{
    protected $table = 'settlements';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'household_id',
        'expense_group_id',
        'from_user_id',
        'to_user_id',
        'attachment_id',
        'settlement_date',
        'currency',
        'amount',
        'payment_method',
        'note',
        'created_by',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        return $this->select([
                'settlements.*',
                'payer.display_name AS from_user_name',
                'payer.email AS from_user_email',
                'receiver.display_name AS to_user_name',
                'receiver.email AS to_user_email',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
                'attachments.original_name AS attachment_original_name',
            ])
            ->join('users AS payer', 'payer.id = settlements.from_user_id', 'inner')
            ->join('users AS receiver', 'receiver.id = settlements.to_user_id', 'inner')
            ->join('expense_groups', 'expense_groups.id = settlements.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->join('attachments', 'attachments.id = settlements.attachment_id AND attachments.deleted_at IS NULL', 'left')
            ->where('settlements.household_id', $householdId)
            ->orderBy('settlements.settlement_date', 'DESC')
            ->orderBy('settlements.created_at', 'DESC')
            ->findAll();
    }

    public function findDetailForHousehold(int $householdId, int $settlementId): ?array
    {
        return $this->select([
                'settlements.*',
                'payer.display_name AS from_user_name',
                'payer.email AS from_user_email',
                'receiver.display_name AS to_user_name',
                'receiver.email AS to_user_email',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
                'attachments.original_name AS attachment_original_name',
                'attachments.mime_type AS attachment_mime_type',
                'attachments.path AS attachment_path',
                'attachments.disk AS attachment_disk',
            ])
            ->join('users AS payer', 'payer.id = settlements.from_user_id', 'inner')
            ->join('users AS receiver', 'receiver.id = settlements.to_user_id', 'inner')
            ->join('expense_groups', 'expense_groups.id = settlements.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->join('attachments', 'attachments.id = settlements.attachment_id AND attachments.deleted_at IS NULL', 'left')
            ->where('settlements.household_id', $householdId)
            ->where('settlements.id', $settlementId)
            ->first();
    }
}
