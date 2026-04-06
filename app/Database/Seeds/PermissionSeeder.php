<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Authorization\Permission;
use App\Models\Authorization\PermissionModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $model = new PermissionModel($this->db);
        $now = Time::now()->toDateTimeString();

        foreach (Permission::definitions() as $code => $definition) {
            $existing = $model->where('code', $code)->first();

            $payload = [
                'code' => $code,
                'name' => $definition['name'],
                'module' => $definition['module'],
                'description' => $definition['description'],
                'updated_at' => $now,
            ];

            if ($existing === null) {
                $payload['created_at'] = $now;
                $model->insert($payload);
                continue;
            }

            $model->update((int) $existing['id'], $payload);
        }
    }
}
