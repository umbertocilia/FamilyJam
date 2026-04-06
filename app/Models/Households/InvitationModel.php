<?php

declare(strict_types=1);

namespace App\Models\Households;

use App\Models\BaseModel;

final class InvitationModel extends BaseModel
{
    protected $table = 'invitations';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'household_id',
        'email',
        'role_id',
        'token_hash',
        'invited_by_user_id',
        'message',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ];

    public function findActiveByRawToken(string $rawToken): ?array
    {
        $now = date('Y-m-d H:i:s');

        return $this->select('invitations.*, households.name AS household_name, households.slug AS household_slug, roles.code AS role_code, roles.name AS role_name')
            ->join('households', 'households.id = invitations.household_id', 'inner')
            ->join('roles', 'roles.id = invitations.role_id', 'left')
            ->where('invitations.token_hash', hash('sha256', $rawToken))
            ->where('invitations.accepted_at', null)
            ->where('invitations.revoked_at', null)
            ->groupStart()
                ->where('invitations.expires_at', null)
                ->orWhere('invitations.expires_at >=', $now)
            ->groupEnd()
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPendingForHousehold(int $householdId): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->select('invitations.*, roles.code AS role_code, roles.name AS role_name, inviter.display_name AS invited_by_name')
            ->join('roles', 'roles.id = invitations.role_id', 'left')
            ->join('users AS inviter', 'inviter.id = invitations.invited_by_user_id', 'left')
            ->where('invitations.household_id', $householdId)
            ->where('invitations.accepted_at', null)
            ->where('invitations.revoked_at', null)
            ->groupStart()
                ->where('invitations.expires_at', null)
                ->orWhere('invitations.expires_at >=', $now)
            ->groupEnd()
            ->orderBy('invitations.created_at', 'DESC')
            ->findAll();
    }

    public function findForHousehold(int $householdId, int $invitationId): ?array
    {
        return $this->where('household_id', $householdId)
            ->where('id', $invitationId)
            ->first();
    }
}
