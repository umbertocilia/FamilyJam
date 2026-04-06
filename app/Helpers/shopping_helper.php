<?php

declare(strict_types=1);

if (! function_exists('shopping_priority_label')) {
    function shopping_priority_label(string $priority): string
    {
        return match (strtolower(trim($priority))) {
            'urgent' => 'Urgent',
            'high' => 'High',
            'low' => 'Low',
            default => 'Normal',
        };
    }
}

if (! function_exists('shopping_priority_badge_class')) {
    function shopping_priority_badge_class(string $priority): string
    {
        return match (strtolower(trim($priority))) {
            'urgent' => 'badge--shopping-urgent',
            'high' => 'badge--expense-step',
            'low' => 'badge--expense-deleted',
            default => 'badge--shopping-open',
        };
    }
}

if (! function_exists('shopping_quantity_label')) {
    function shopping_quantity_label(string $quantity, ?string $unit = null): string
    {
        $resolvedUnit = trim((string) $unit);

        if ($resolvedUnit === '') {
            return $quantity;
        }

        return $quantity . ' ' . $resolvedUnit;
    }
}
