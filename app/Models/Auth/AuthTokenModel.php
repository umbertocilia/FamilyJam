<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\BaseModel;
use CodeIgniter\I18n\Time;

final class AuthTokenModel extends BaseModel
{
    protected $table = 'auth_tokens';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'type',
        'token_hash',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_ip',
        'user_agent',
    ];

    public function findValidByRawToken(string $rawToken, string $type): ?array
    {
        $now = Time::now()->toDateTimeString();

        return $this->where('token_hash', hash('sha256', $rawToken))
            ->where('type', $type)
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->groupStart()
                ->where('expires_at', null)
                ->orWhere('expires_at >=', $now)
            ->groupEnd()
            ->first();
    }

    public function revokeOutstanding(int $userId, string $type): void
    {
        $this->where('user_id', $userId)
            ->where('type', $type)
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->set(['revoked_at' => Time::now()->toDateTimeString()])
            ->update();
    }
}
