<?php

declare(strict_types=1);

if (! function_exists('expense_split_label')) {
    function expense_split_label(string $splitMethod): string
    {
        return match ($splitMethod) {
            'equal' => ui_text('expense.method.equal'),
            'exact' => ui_text('expense.method.exact'),
            'percentage' => ui_text('expense.method.percentage'),
            'shares' => ui_text('expense.method.shares'),
            default => ucfirst($splitMethod),
        };
    }
}

if (! function_exists('expense_status_label')) {
    function expense_status_label(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'edited' => 'Edited',
            'deleted' => 'Deleted',
            'disputed' => 'Disputed',
            'posted' => 'Active',
            default => ucfirst($status),
        };
    }
}

if (! function_exists('expense_category_name')) {
    /**
     * @param array<string, mixed>|null $category
     */
    function expense_category_name(?array $category, ?string $fallback = null): string
    {
        if ($category === null) {
            return ui_text('category.uncategorized');
        }

        return ui_category_name(
            isset($category['category_code']) ? (string) $category['category_code'] : (isset($category['code']) ? (string) $category['code'] : null),
            isset($category['category_name']) ? (string) $category['category_name'] : (isset($category['name']) ? (string) $category['name'] : $fallback),
            ! empty($category['category_is_system']) || ! empty($category['is_system']),
        );
    }
}

if (! function_exists('expense_status_badge_class')) {
    function expense_status_badge_class(string $status): string
    {
        return match ($status) {
            'active', 'posted' => 'badge--expense-active',
            'edited' => 'badge--expense-edited',
            'deleted' => 'badge--expense-deleted',
            'disputed' => 'badge--expense-disputed',
            default => 'badge--expense-edited',
        };
    }
}

if (! function_exists('money_format')) {
    function money_format(string|float|int $amount, string $currency): string
    {
        return number_format((float) $amount, 2, '.', ',') . ' ' . strtoupper($currency);
    }
}
