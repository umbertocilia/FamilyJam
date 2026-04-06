<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateExpenseGroupsTables extends BaseMigration
{
    public function up(): void
    {
        $this->createExpenseGroups();
        $this->createExpenseGroupMembers();
        $this->addExpenseGroupColumns();
    }

    public function down(): void
    {
        if ($this->db->fieldExists('expense_group_id', 'settlements')) {
            $this->db->query('ALTER TABLE `settlements` DROP FOREIGN KEY `fk_settlements_expense_group`');
            $this->db->query('ALTER TABLE `settlements` DROP INDEX `idx_settlements_household_group_date`');
            $this->db->query('ALTER TABLE `settlements` DROP COLUMN `expense_group_id`');
        }

        if ($this->db->fieldExists('expense_group_id', 'expenses')) {
            $this->db->query('ALTER TABLE `expenses` DROP FOREIGN KEY `fk_expenses_expense_group`');
            $this->db->query('ALTER TABLE `expenses` DROP INDEX `idx_expenses_household_group_date`');
            $this->db->query('ALTER TABLE `expenses` DROP COLUMN `expense_group_id`');
        }

        $this->forge->dropTable('expense_group_members', true);
        $this->forge->dropTable('expense_groups', true);
    }

    private function createExpenseGroups(): void
    {
        if ($this->db->tableExists('expense_groups')) {
            return;
        }

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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
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
                'null' => true,
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
        $this->forge->addKey(['household_id', 'is_active', 'name'], false, false, 'idx_expense_groups_household_active_name');
        $this->forge->addUniqueKey(['household_id', 'name'], 'uq_expense_groups_household_name');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('expense_groups', true);
        $this->applyTableDefaults('expense_groups');
    }

    private function createExpenseGroupMembers(): void
    {
        if ($this->db->tableExists('expense_group_members')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'expense_group_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
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
        $this->forge->addUniqueKey(['expense_group_id', 'user_id'], 'uq_expense_group_members_group_user');
        $this->forge->addForeignKey('expense_group_id', 'expense_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('expense_group_members', true);
        $this->applyTableDefaults('expense_group_members');
    }

    private function addExpenseGroupColumns(): void
    {
        if (! $this->db->fieldExists('expense_group_id', 'expenses')) {
            $this->db->query(
                'ALTER TABLE `expenses`
                    ADD COLUMN `expense_group_id` BIGINT UNSIGNED NULL AFTER `category_id`,
                    ADD KEY `idx_expenses_household_group_date` (`household_id`, `expense_group_id`, `expense_date`),
                    ADD CONSTRAINT `fk_expenses_expense_group`
                        FOREIGN KEY (`expense_group_id`) REFERENCES `expense_groups` (`id`)
                        ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }

        if (! $this->db->fieldExists('expense_group_id', 'settlements')) {
            $this->db->query(
                'ALTER TABLE `settlements`
                    ADD COLUMN `expense_group_id` BIGINT UNSIGNED NULL AFTER `household_id`,
                    ADD KEY `idx_settlements_household_group_date` (`household_id`, `expense_group_id`, `settlement_date`),
                    ADD CONSTRAINT `fk_settlements_expense_group`
                        FOREIGN KEY (`expense_group_id`) REFERENCES `expense_groups` (`id`)
                        ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }
    }
}
