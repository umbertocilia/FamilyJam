<?php

declare(strict_types=1);

namespace App\Models\Legal;

use App\Models\BaseModel;

final class PrivacyConsentModel extends BaseModel
{
    protected $table = 'privacy_consents';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'consent_uuid',
        'user_id',
        'locale',
        'policy_version',
        'consent_source',
        'necessary',
        'preferences',
        'analytics',
        'marketing',
        'consented_at',
        'withdrawn_at',
    ];

    public function activeByConsentUuid(string $consentUuid): ?array
    {
        return $this->where('consent_uuid', $consentUuid)
            ->where('withdrawn_at', null)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function latestActiveByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('withdrawn_at', null)
            ->orderBy('id', 'DESC')
            ->first();
    }
}
