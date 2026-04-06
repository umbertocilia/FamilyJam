<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateAttachmentTables extends BaseMigration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'household_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'uploaded_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'original_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'stored_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_size' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'disk' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'local',
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'checksum_sha256' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['disk', 'path'], 'uq_attachments_disk_path');
        $this->forge->addKey(['household_id', 'entity_type', 'entity_id'], false, false, 'idx_attachments_household_entity');
        $this->forge->addKey(['uploaded_by', 'created_at'], false, false, 'idx_attachments_uploader_created');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('attachments', true);
        $this->applyTableDefaults('attachments');
    }

    public function down(): void
    {
        $this->forge->dropTable('attachments', true);
    }
}
