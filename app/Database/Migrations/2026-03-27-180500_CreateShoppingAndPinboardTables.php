<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateShoppingAndPinboardTables extends BaseMigration
{
    public function up(): void
    {
        $this->createShoppingLists();
        $this->createShoppingItems();
        $this->createPinboardPosts();
        $this->createPinboardComments();
    }

    public function down(): void
    {
        $this->forge->dropTable('pinboard_comments', true);
        $this->forge->dropTable('pinboard_posts', true);
        $this->forge->dropTable('shopping_items', true);
        $this->forge->dropTable('shopping_lists', true);
    }

    private function createShoppingLists(): void
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'is_default' => [
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
        $this->forge->addKey(['household_id', 'is_default'], false, false, 'idx_shopping_lists_household_default');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('shopping_lists', true);
        $this->applyTableDefaults('shopping_lists');
    }

    private function createShoppingItems(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'shopping_list_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'household_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'quantity' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '1.00',
            ],
            'unit' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'priority' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'normal',
            ],
            'assigned_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'position' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'is_purchased' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'purchased_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'purchased_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'converted_expense_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
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
        $this->forge->addKey(['shopping_list_id', 'is_purchased', 'position'], false, false, 'idx_shopping_items_list_purchased_position');
        $this->forge->addForeignKey('shopping_list_id', 'shopping_lists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('purchased_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('converted_expense_id', 'expenses', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('shopping_items', true);
        $this->applyTableDefaults('shopping_items');
    }

    private function createPinboardPosts(): void
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
            'author_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'body' => [
                'type' => 'LONGTEXT',
            ],
            'post_type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'note',
            ],
            'is_pinned' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'due_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['household_id', 'is_pinned', 'created_at'], false, false, 'idx_pinboard_posts_household_pinned_created');
        $this->forge->addForeignKey('household_id', 'households', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('author_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('pinboard_posts', true);
        $this->applyTableDefaults('pinboard_posts');
    }

    private function createPinboardComments(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'post_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'author_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'body' => [
                'type' => 'TEXT',
            ],
            ...$this->timestampFields(),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['post_id', 'created_at'], false, false, 'idx_pinboard_comments_post_created');
        $this->forge->addForeignKey('post_id', 'pinboard_posts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('author_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('pinboard_comments', true);
        $this->applyTableDefaults('pinboard_comments');
    }
}
