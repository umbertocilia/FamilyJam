<?php

declare(strict_types=1);

if (! function_exists('chore_status_label')) {
    function chore_status_label(string $status): string
    {
        helper('ui');

        return match ($status) {
            'pending' => ui_locale() === 'it' ? 'In attesa' : 'Pending',
            'completed' => ui_locale() === 'it' ? 'Completata' : 'Completed',
            'skipped' => ui_locale() === 'it' ? 'Saltata' : 'Skipped',
            'overdue' => ui_locale() === 'it' ? 'Scaduta' : 'Overdue',
            default => ucfirst($status),
        };
    }
}

if (! function_exists('chore_status_badge_class')) {
    function chore_status_badge_class(string $status): string
    {
        return match ($status) {
            'pending' => 'badge--chore-pending',
            'completed' => 'badge--chore-completed',
            'skipped' => 'badge--chore-skipped',
            'overdue' => 'badge--chore-overdue',
            default => 'badge--medium',
        };
    }
}

if (! function_exists('chore_assignment_label')) {
    function chore_assignment_label(string $mode): string
    {
        helper('ui');

        return match ($mode) {
            'fixed' => ui_locale() === 'it' ? 'Assegnazione fissa' : 'Fixed assignee',
            'rotation' => ui_locale() === 'it' ? 'Rotazione' : 'Rotation',
            default => ucfirst($mode),
        };
    }
}
