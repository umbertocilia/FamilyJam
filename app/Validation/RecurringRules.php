<?php

declare(strict_types=1);

namespace App\Validation;

final class RecurringRules
{
    public function valid_recurring_frequency(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true);
    }

    public function valid_recurring_custom_unit(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        return in_array(strtolower(trim($value)), ['day', 'week', 'month', 'year'], true);
    }
}
