<?php

declare(strict_types=1);

namespace App\Validation;

final class ChoreRules
{
    public function valid_chore_assignment_mode(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['fixed', 'rotation'], true);
    }

    public function valid_chore_occurrence_status(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        return in_array(strtolower(trim($value)), ['pending', 'completed', 'skipped', 'overdue'], true);
    }
}
