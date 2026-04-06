<?php

declare(strict_types=1);

if (! function_exists('chore_status_label')) {
    function chore_status_label(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'skipped' => 'Skipped',
            'overdue' => 'Overdue',
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
        return match ($mode) {
            'fixed' => 'Fixed assignee',
            'rotation' => 'Rotation',
            default => ucfirst($mode),
        };
    }
}
