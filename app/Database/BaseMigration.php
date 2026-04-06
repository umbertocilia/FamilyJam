<?php

declare(strict_types=1);

namespace App\Database;

use CodeIgniter\Database\Migration;

abstract class BaseMigration extends Migration
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function timestampFields(bool $softDelete = true): array
    {
        $fields = [
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        if ($softDelete) {
            $fields['deleted_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        return $fields;
    }

    protected function applyTableDefaults(string $table): void
    {
        $this->db->query(sprintf(
            'ALTER TABLE `%s` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $table,
        ));
    }
}
