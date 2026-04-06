<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class AlignExpenseStatusValues extends BaseMigration
{
    public function up(): void
    {
        $this->db->query("UPDATE expenses SET status = 'active' WHERE status = 'posted'");
        $this->db->query("ALTER TABLE expenses MODIFY status VARCHAR(24) NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        $this->db->query("UPDATE expenses SET status = 'posted' WHERE status = 'active'");
        $this->db->query("ALTER TABLE expenses MODIFY status VARCHAR(24) NOT NULL DEFAULT 'posted'");
    }
}
