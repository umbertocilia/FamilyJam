<?php

declare(strict_types=1);

if (! function_exists('pinboard_post_type_label')) {
    function pinboard_post_type_label(string $type): string
    {
        return match (strtolower(trim($type))) {
            'announcement' => 'Announcement',
            'todo' => 'Todo',
            default => 'Note',
        };
    }
}

if (! function_exists('pinboard_post_type_badge_class')) {
    function pinboard_post_type_badge_class(string $type): string
    {
        return match (strtolower(trim($type))) {
            'announcement' => 'badge--expense-active',
            'todo' => 'badge--shopping-open',
            default => 'badge--expense-step',
        };
    }
}
