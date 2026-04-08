<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreatePrivacyConsentTable extends BaseMigration
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
            'consent_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'it',
            ],
            'policy_version' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
            ],
            'consent_source' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'banner',
            ],
            'necessary' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'preferences' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'analytics' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'marketing' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'consented_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'withdrawn_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            ...$this->timestampFields(false),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['consent_uuid', 'withdrawn_at'], false, false, 'idx_privacy_consents_uuid_active');
        $this->forge->addKey(['user_id', 'withdrawn_at'], false, false, 'idx_privacy_consents_user_active');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('privacy_consents', true);
        $this->applyTableDefaults('privacy_consents');
    }

    public function down(): void
    {
        $this->forge->dropTable('privacy_consents', true);
    }
}
