<?php

declare(strict_types=1);

namespace App\Validation;

final class ShoppingRules
{
    public function valid_shopping_priority(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        return in_array(strtolower(trim($value)), ['urgent', 'high', 'normal', 'low'], true);
    }
}
