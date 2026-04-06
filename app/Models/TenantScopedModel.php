<?php

declare(strict_types=1);

namespace App\Models;

abstract class TenantScopedModel extends BaseModel
{
    protected string $tenantColumn = 'household_id';

    public function forHousehold(int $householdId): static
    {
        $this->where($this->tenantColumn, $householdId);

        return $this;
    }

    public function firstForHousehold(int $householdId): ?array
    {
        return $this->forHousehold($householdId)->first();
    }
}
