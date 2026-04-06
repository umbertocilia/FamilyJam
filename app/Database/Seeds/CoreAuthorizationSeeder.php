<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

final class CoreAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(BaseRoleSeeder::class);
        $this->call(BaseRolePermissionSeeder::class);
    }
}
