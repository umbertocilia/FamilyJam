<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateAuthorizationTables extends BaseMigration
{
    public function up(): void
    {
        $this->createRoles();
        $this->createPermissions();
        $this->createRolePermissions();
        $this->createHouseholdMemberships();
        $this->createMembershipRoles();
        $this->createInvitations();
    }

    public function down(): void
    {
        $this->forge->dropTable('invitations', true);
        $this->forge->dropTable('membership_roles', true);
        $this->forge->dropTable('household_memberships', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
    }

    private function createRoles(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NULL,
    `scope_household_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `code` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `is_assignable` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_scope_code` (`scope_household_id`, `code`),
    KEY `idx_roles_household_system` (`household_id`, `is_system`),
    CONSTRAINT `fk_roles_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $this->db->query($sql);
    }

    private function createPermissions(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'module' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            ...$this->timestampFields(false),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_permissions_code');
        $this->forge->addKey('module', false, false, 'idx_permissions_module');
        $this->forge->createTable('permissions', true);
        $this->applyTableDefaults('permissions');
    }

    private function createRolePermissions(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'permission_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id'], 'uq_role_permissions_role_permission');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions', true);
        $this->applyTableDefaults('role_permissions');
    }

    private function createHouseholdMemberships(): void
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
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'invited_by_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 24,
                'default' => 'active',
            ],
            'nickname' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'joined_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['household_id', 'user_id'], 'uq_household_memberships_household_user');
        $this->forge->addKey(['household_id', 'status'], false, false, 'idx_household_memberships_household_status');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('invited_by_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('household_memberships', true);
        $this->applyTableDefaults('household_memberships');
    }

    private function createMembershipRoles(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'membership_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'role_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'assigned_by_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['membership_id', 'role_id'], 'uq_membership_roles_membership_role');
        $this->forge->addKey('role_id', false, false, 'idx_membership_roles_role');
        $this->forge->addForeignKey('membership_id', 'household_memberships', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('assigned_by_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('membership_roles', true);
        $this->applyTableDefaults('membership_roles');
    }

    private function createInvitations(): void
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
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'role_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'token_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
            ],
            'invited_by_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accepted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
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
        $this->forge->addUniqueKey('token_hash', 'uq_invitations_token_hash');
        $this->forge->addKey(['household_id', 'email'], false, false, 'idx_invitations_household_email');
        $this->forge->addKey('expires_at', false, false, 'idx_invitations_expires_at');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('invited_by_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('invitations', true);
        $this->applyTableDefaults('invitations');
    }
}
