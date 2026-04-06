<?php

declare(strict_types=1);

namespace App\Validation;

final class PinboardRules
{
    public function valid_post_type(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        return in_array(strtolower(trim($value)), ['note', 'announcement', 'todo'], true);
    }
}
