<?php

declare(strict_types=1);

if (! function_exists('recurring_frequency_label')) {
    function recurring_frequency_label(string $frequency, ?string $customUnit = null, ?int $intervalValue = null): string
    {
        helper('ui');
        $interval = $intervalValue ?? 1;

        return match ($frequency) {
            'daily' => ui_locale() === 'it'
                ? ($interval === 1 ? 'Giornaliera' : 'Ogni ' . $interval . ' giorni')
                : ($interval === 1 ? 'Daily' : 'Every ' . $interval . ' days'),
            'weekly' => ui_locale() === 'it'
                ? ($interval === 1 ? 'Settimanale' : 'Ogni ' . $interval . ' settimane')
                : ($interval === 1 ? 'Weekly' : 'Every ' . $interval . ' weeks'),
            'monthly' => ui_locale() === 'it'
                ? ($interval === 1 ? 'Mensile' : 'Ogni ' . $interval . ' mesi')
                : ($interval === 1 ? 'Monthly' : 'Every ' . $interval . ' months'),
            'yearly' => ui_locale() === 'it'
                ? ($interval === 1 ? 'Annuale' : 'Ogni ' . $interval . ' anni')
                : ($interval === 1 ? 'Yearly' : 'Every ' . $interval . ' years'),
            'custom' => ui_locale() === 'it'
                ? 'Personalizzata ' . ($customUnit ?? 'intervallo') . ' x' . $interval
                : 'Custom ' . ($customUnit ?? 'interval') . ' x' . $interval,
            default => ucfirst($frequency),
        };
    }
}

if (! function_exists('weekday_label')) {
    function weekday_label(int $weekday): string
    {
        helper('ui');

        return match ($weekday) {
            1 => ui_locale() === 'it' ? 'Lun' : 'Mon',
            2 => ui_locale() === 'it' ? 'Mar' : 'Tue',
            3 => ui_locale() === 'it' ? 'Mer' : 'Wed',
            4 => ui_locale() === 'it' ? 'Gio' : 'Thu',
            5 => ui_locale() === 'it' ? 'Ven' : 'Fri',
            6 => ui_locale() === 'it' ? 'Sab' : 'Sat',
            7 => ui_locale() === 'it' ? 'Dom' : 'Sun',
            default => (string) $weekday,
        };
    }
}
