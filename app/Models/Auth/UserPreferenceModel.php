<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\BaseModel;

final class UserPreferenceModel extends BaseModel
{
    protected $table = 'user_preferences';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'default_household_id',
        'notification_preferences_json',
        'dashboard_preferences_json',
    ];

    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }
}
