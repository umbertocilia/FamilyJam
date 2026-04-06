<?php

declare(strict_types=1);

if (! function_exists('balance_direction_label')) {
    function balance_direction_label(string $direction): string
    {
        helper('ui');

        return match ($direction) {
            'gets_back' => ui_text('report.balance.gets_back'),
            'owes' => ui_text('report.balance.owes'),
            'settled' => ui_locale() === 'it' ? 'In pari' : 'Settled',
            default => ucfirst($direction),
        };
    }
}

if (! function_exists('balance_direction_badge_class')) {
    function balance_direction_badge_class(string $direction): string
    {
        return match ($direction) {
            'gets_back' => 'badge badge--expense-active',
            'owes' => 'badge badge--expense-deleted',
            'settled' => 'badge badge--expense-step',
            default => 'badge badge--expense-step',
        };
    }
}
