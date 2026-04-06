<?php

declare(strict_types=1);

namespace App\Validation;

final class ExpenseRules
{
    public function valid_split_method(?string $value): bool
    {
        return $value !== null && in_array($value, ['equal', 'exact', 'percentage', 'shares'], true);
    }

    public function positive_money(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) && (float) $normalized > 0;
    }

    public function valid_month_filter(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return preg_match('/^\d{4}-\d{2}$/', $value) === 1;
    }
}
