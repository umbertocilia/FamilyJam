<?php

declare(strict_types=1);

if (! function_exists('recurring_frequency_label')) {
    function recurring_frequency_label(string $frequency, ?string $customUnit = null, ?int $intervalValue = null): string
    {
        $interval = $intervalValue ?? 1;

        return match ($frequency) {
            'daily' => $interval === 1 ? 'Daily' : 'Every ' . $interval . ' days',
            'weekly' => $interval === 1 ? 'Weekly' : 'Every ' . $interval . ' weeks',
            'monthly' => $interval === 1 ? 'Monthly' : 'Every ' . $interval . ' months',
            'yearly' => $interval === 1 ? 'Yearly' : 'Every ' . $interval . ' years',
            'custom' => 'Custom ' . ($customUnit ?? 'interval') . ' x' . $interval,
            default => ucfirst($frequency),
        };
    }
}

if (! function_exists('weekday_label')) {
    function weekday_label(int $weekday): string
    {
        return match ($weekday) {
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
            default => (string) $weekday,
        };
    }
}
