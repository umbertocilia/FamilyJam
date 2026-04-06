<?php

declare(strict_types=1);

namespace App\Models\Authorization;

use App\Models\BaseModel;

final class PermissionModel extends BaseModel
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'code',
        'name',
        'module',
        'description',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllOrdered(): array
    {
        return $this->orderBy('module', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @param list<string> $codes
     * @return list<array<string, mixed>>
     */
    public function findByCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return $this->whereIn('code', $codes)
            ->orderBy('module', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @param list<string> $codes
     * @return array<string, int>
     */
    public function findIdsByCodes(array $codes): array
    {
        $rows = $this->select(['id', 'code'])
            ->whereIn('code', $codes)
            ->findAll();

        $mapped = [];

        foreach ($rows as $row) {
            $mapped[$row['code']] = (int) $row['id'];
        }

        return $mapped;
    }
}
