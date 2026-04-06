<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\BaseModel;

final class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'display_name',
        'avatar_path',
        'locale',
        'theme',
        'timezone',
        'email_verified_at',
        'last_login_at',
        'status',
    ];

    public function findByEmail(string $email, bool $withDeleted = false): ?array
    {
        $builder = $withDeleted ? $this->withDeleted() : $this;

        return $builder->where('email', strtolower(trim($email)))->first();
    }

    public function findActiveByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))
            ->where('status', 'active')
            ->first();
    }

    public function findActiveById(int $userId): ?array
    {
        return $this->where('id', $userId)
            ->where('status', 'active')
            ->first();
    }
}
