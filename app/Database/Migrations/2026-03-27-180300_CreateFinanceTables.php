<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateFinanceTables extends BaseMigration
{
    public function up(): void
    {
        $this->createExpenseCategories();
        $this->createRecurringRules();
        $this->createExpenses();
        $this->createExpensePayers();
        $this->createExpenseSplits();
        $this->createSettlements();
    }

    public function down(): void
    {
        $this->forge->dropTable('settlements', true);
        $this->forge->dropTable('expense_splits', true);
        $this->forge->dropTable('expense_payers', true);
        $this->forge->dropTable('expenses', true);
        $this->forge->dropTable('recurring_rules', true);
        $this->forge->dropTable('expense_categories', true);
    }

    private function createExpenseCategories(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NULL,
    `scope_household_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `code` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `color` VARCHAR(20) NULL,
    `icon` VARCHAR(50) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_expense_categories_scope_code` (`scope_household_id`, `code`),
    KEY `idx_expense_categories_household_active_sort` (`household_id`, `is_active`, `sort_order`),
    CONSTRAINT `fk_expense_categories_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_expense_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $this->db->query($sql);
    }

    private function createRecurringRules(): void
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
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'frequency' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'interval_value' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
            ],
            'by_weekday' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'day_of_month' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => true,
                'null' => true,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
            ],
            'ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'next_run_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_run_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'config_json' => [
                'type' => 'JSON',
                'null' => true,
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
        $this->forge->addKey(['household_id', 'entity_type', 'is_active', 'next_run_at'], false, false, 'idx_recurring_rules_due');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('recurring_rules', true);
        $this->applyTableDefaults('recurring_rules');
    }

    private function createExpenses(): void
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
            'category_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'receipt_attachment_id' => [
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
            'expense_date' => [
                'type' => 'DATE',
            ],
            'currency' => [
                'type' => 'CHAR',
                'constraint' => 3,
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'split_method' => [
                'type' => 'VARCHAR',
                'constraint' => 24,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 24,
                'default' => 'posted',
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
        $this->forge->addKey(['household_id', 'expense_date'], false, false, 'idx_expenses_household_date');
        $this->forge->addKey(['household_id', 'status', 'expense_date'], false, false, 'idx_expenses_household_status_date');
        $this->forge->addKey(['household_id', 'created_by', 'expense_date'], false, false, 'idx_expenses_household_creator_date');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recurring_rule_id', 'recurring_rules', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('category_id', 'expense_categories', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('receipt_attachment_id', 'attachments', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('expenses', true);
        $this->applyTableDefaults('expenses');
    }

    private function createExpensePayers(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'expense_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'amount_paid' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
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
        $this->forge->addUniqueKey(['expense_id', 'user_id'], 'uq_expense_payers_expense_user');
        $this->forge->addForeignKey('expense_id', 'expenses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('expense_payers', true);
        $this->applyTableDefaults('expense_payers');
    }

    private function createExpenseSplits(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'expense_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'owed_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'percentage' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'share_units' => [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'null' => true,
            ],
            'is_excluded' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        $this->forge->addUniqueKey(['expense_id', 'user_id'], 'uq_expense_splits_expense_user');
        $this->forge->addForeignKey('expense_id', 'expenses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('expense_splits', true);
        $this->applyTableDefaults('expense_splits');
    }

    private function createSettlements(): void
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
            'from_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'to_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'attachment_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'settlement_date' => [
                'type' => 'DATE',
            ],
            'currency' => [
                'type' => 'CHAR',
                'constraint' => 3,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['household_id', 'settlement_date'], false, false, 'idx_settlements_household_date');
        $this->forge->addKey(['household_id', 'from_user_id', 'settlement_date'], false, false, 'idx_settlements_from_user_date');
        $this->forge->addKey(['household_id', 'to_user_id', 'settlement_date'], false, false, 'idx_settlements_to_user_date');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('from_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('to_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('attachment_id', 'attachments', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('settlements', true);
        $this->applyTableDefaults('settlements');
    }
}
