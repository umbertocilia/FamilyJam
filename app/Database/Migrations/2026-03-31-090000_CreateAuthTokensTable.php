<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class CreateAuthTokensTable extends BaseMigration
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
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
            ],
            'token_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_ip' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            ...$this->timestampFields(false),
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash', 'uq_auth_tokens_token_hash');
        $this->forge->addKey(['user_id', 'type'], false, false, 'idx_auth_tokens_user_type');
        $this->forge->addKey(['type', 'expires_at'], false, false, 'idx_auth_tokens_type_expires');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('auth_tokens', true);
        $this->applyTableDefaults('auth_tokens');
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_tokens', true);
    }
}
