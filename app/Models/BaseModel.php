<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

abstract class BaseModel extends Model
{
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected bool $allowEmptyInserts = false;
    protected $skipValidation = false;
}
