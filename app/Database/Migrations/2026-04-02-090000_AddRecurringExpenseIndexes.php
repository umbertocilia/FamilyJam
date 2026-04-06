<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class AddRecurringExpenseIndexes extends BaseMigration
{
    public function up(): void
    {
        $this->db->query('CREATE INDEX idx_expenses_recurring_date ON expenses (recurring_rule_id, expense_date)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX idx_expenses_recurring_date ON expenses');
    }
}
