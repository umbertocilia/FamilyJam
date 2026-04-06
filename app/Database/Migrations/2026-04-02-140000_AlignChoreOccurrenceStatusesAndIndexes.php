<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\BaseMigration;

final class AlignChoreOccurrenceStatusesAndIndexes extends BaseMigration
{
    public function up(): void
    {
        $this->db->query("UPDATE chore_occurrences SET status = 'pending' WHERE status = 'scheduled'");
        $this->db->query("ALTER TABLE chore_occurrences MODIFY status VARCHAR(24) NOT NULL DEFAULT 'pending'");
        $this->db->query('ALTER TABLE chores ADD INDEX idx_chores_recurring_rule (recurring_rule_id)');
        $this->db->query('ALTER TABLE chore_occurrences ADD UNIQUE KEY uq_chore_occurrences_chore_due (chore_id, due_at)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE chore_occurrences DROP INDEX uq_chore_occurrences_chore_due');
        $this->db->query('ALTER TABLE chores DROP INDEX idx_chores_recurring_rule');
        $this->db->query("ALTER TABLE chore_occurrences MODIFY status VARCHAR(24) NOT NULL DEFAULT 'scheduled'");
        $this->db->query("UPDATE chore_occurrences SET status = 'scheduled' WHERE status = 'pending'");
    }
}
