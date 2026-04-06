<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateUserAndHouseholdTables extends BaseMigration
{
    public function up(): void
    {
        $this->createUsers();
        $this->createHouseholds();
        $this->createUserPreferences();
        $this->createHouseholdSettings();
    }

    public function down(): void
    {
        $this->forge->dropTable('household_settings', true);
        $this->forge->dropTable('user_preferences', true);
        $this->forge->dropTable('households', true);
        $this->forge->dropTable('users', true);
    }

    private function createUsers(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'display_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'avatar_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'it',
            ],
            'theme' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'system',
            ],
            'timezone' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'default' => 'Europe/Rome',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 24,
                'default' => 'active',
            ],
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email', 'uq_users_email');
        $this->forge->addKey('status', false, false, 'idx_users_status');
        $this->forge->createTable('users', true);
        $this->applyTableDefaults('users');
    }

    private function createHouseholds(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'avatar_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'base_currency' => [
                'type' => 'CHAR',
                'constraint' => 3,
                'default' => 'EUR',
            ],
            'timezone' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'default' => 'Europe/Rome',
            ],
            'simplify_debts' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'chore_scoring_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'is_archived' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug', 'uq_households_slug');
        $this->forge->addKey(['created_by', 'is_archived'], false, false, 'idx_households_creator_archived');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('households', true);
        $this->applyTableDefaults('households');
    }

    private function createUserPreferences(): void
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
            'default_household_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'notification_preferences_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'dashboard_preferences_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            ...$this->timestampFields(false),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id', 'uq_user_preferences_user');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('default_household_id', 'households', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('user_preferences', true);
        $this->applyTableDefaults('user_preferences');
    }

    private function createHouseholdSettings(): void
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
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'it',
            ],
            'week_starts_on' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 1,
            ],
            'date_format' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'd/m/Y',
            ],
            'time_format' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => '24h',
            ],
            'notification_settings_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'module_settings_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            ...$this->timestampFields(false),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('household_id', 'uq_household_settings_household');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('household_settings', true);
        $this->applyTableDefaults('household_settings');
    }
}
