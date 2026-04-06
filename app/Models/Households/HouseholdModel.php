<?php

declare(strict_types=1);

namespace App\Models\Households;

use App\Models\BaseModel;

final class HouseholdModel extends BaseModel
{
    protected $table = 'households';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'name',
        'slug',
        'description',
        'avatar_path',
        'base_currency',
        'timezone',
        'simplify_debts',
        'chore_scoring_enabled',
        'is_archived',
        'created_by',
    ];

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    public function findByIdentifier(string $identifier): ?array
    {
        if (ctype_digit($identifier)) {
            return $this->find((int) $identifier);
        }

        return $this->findBySlug($identifier);
    }

    public function slugExists(string $slug): bool
    {
        return $this->where('slug', $slug)->countAllResults() > 0;
    }
}
