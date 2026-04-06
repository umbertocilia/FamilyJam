<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateNotificationAndAuditTables extends BaseMigration
{
    public function up(): void
    {
        $this->createNotifications();
        $this->createAuditLogs();
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('notifications', true);
    }

    private function createNotifications(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'household_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'data_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'read_at', 'created_at'], false, false, 'idx_notifications_user_read_created');
        $this->forge->addKey(['household_id', 'created_at'], false, false, 'idx_notifications_household_created');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('notifications', true);
        $this->applyTableDefaults('notifications');
    }

    private function createAuditLogs(): void
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
            'actor_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'entity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'before_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'after_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['household_id', 'created_at'], false, false, 'idx_audit_logs_household_created');
        $this->forge->addKey(['actor_user_id', 'created_at'], false, false, 'idx_audit_logs_actor_created');
        $this->forge->addKey(['entity_type', 'entity_id', 'created_at'], false, false, 'idx_audit_logs_entity_created');
        $this->forge->addKey(['action', 'created_at'], false, false, 'idx_audit_logs_action_created');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('audit_logs', true);
        $this->applyTableDefaults('audit_logs');
    }
}
