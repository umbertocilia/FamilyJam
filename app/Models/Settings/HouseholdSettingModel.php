<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\BaseModel;

final class HouseholdSettingModel extends BaseModel
{
    protected $table = 'household_settings';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'household_id',
        'locale',
        'week_starts_on',
        'date_format',
        'time_format',
        'notification_settings_json',
        'module_settings_json',
    ];

    public function findByHouseholdId(int $householdId): ?array
    {
        return $this->where('household_id', $householdId)->first();
    }
}
