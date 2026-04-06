<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateChoreTables extends BaseMigration
{
    public function up(): void
    {
        $this->createChores();
        $this->createChoreOccurrences();
    }

    public function down(): void
    {
        $this->forge->dropTable('chore_occurrences', true);
        $this->forge->dropTable('chores', true);
    }

    private function createChores(): void
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
            ],
            'recurring_rule_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'assignment_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'fixed',
            ],
            'fixed_assignee_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'rotation_anchor_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'points' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'estimated_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'updated_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['household_id', 'is_active'], false, false, 'idx_chores_household_active');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recurring_rule_id', 'recurring_rules', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('fixed_assignee_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('rotation_anchor_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('chores', true);
        $this->applyTableDefaults('chores');
    }

    private function createChoreOccurrences(): void
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
            ],
            'chore_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'assigned_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'due_at' => [
                'type' => 'DATETIME',
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'skipped_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'skipped_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'skip_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 24,
                'default' => 'scheduled',
            ],
            'points_awarded' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'reminder_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['household_id', 'status', 'due_at'], false, false, 'idx_chore_occurrences_household_status_due');
        $this->forge->addKey(['assigned_user_id', 'status', 'due_at'], false, false, 'idx_chore_occurrences_assignee_status_due');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('chore_id', 'chores', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('completed_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('skipped_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('chore_occurrences', true);
        $this->applyTableDefaults('chore_occurrences');
    }
}
