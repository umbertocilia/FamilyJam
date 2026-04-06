<?php

declare(strict_types=1);

if (! function_exists('notification_type_label')) {
    function notification_type_label(string $type): string
    {
        return match ($type) {
            'invitation_received' => ui_text('notification.type.invitation_received'),
            'expense_created' => ui_text('notification.type.expense_created'),
            'expense_updated' => ui_text('notification.type.expense_updated'),
            'settlement_created' => ui_text('notification.type.settlement_created'),
            'chore_assigned' => ui_text('notification.type.chore_assigned'),
            'chore_due_soon' => ui_text('notification.type.chore_due_soon'),
            'chore_completed' => ui_text('notification.type.chore_completed'),
            'pinboard_post_created' => ui_text('notification.type.pinboard_post_created'),
            'pinboard_comment_created' => ui_text('notification.type.pinboard_comment_created'),
            default => ui_text('notification.center.title'),
        };
    }
}

if (! function_exists('notification_badge_class')) {
    function notification_badge_class(array $notification): string
    {
        $type = (string) ($notification['type'] ?? '');
        $isRead = ! empty($notification['read_at']);

        if (! $isRead) {
            return 'badge badge--expense-active';
        }

        return match ($type) {
            'chore_due_soon' => 'badge badge--chore-overdue',
            'expense_updated', 'settlement_created' => 'badge badge--expense-edited',
            default => 'badge badge--expense-step',
        };
    }
}
