<?php

declare(strict_types=1);

if (! function_exists('report_bar_width')) {
    function report_bar_width(int|float|string $value, int|float|string $max, float $minimum = 6.0): string
    {
        $valueFloat = max(0.0, (float) $value);
        $maxFloat = max(0.0, (float) $max);

        if ($maxFloat <= 0.0 || $valueFloat <= 0.0) {
            return '0%';
        }

        $width = max($minimum, min(100.0, round(($valueFloat / $maxFloat) * 100, 2)));

        return number_format($width, 2, '.', '') . '%';
    }
}

if (! function_exists('report_period_label')) {
    function report_period_label(string $period): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m', $period);

        if ($date === false) {
            return $period;
        }

        return ucfirst($date->format('M Y'));
    }
}

if (! function_exists('report_date_label')) {
    function report_date_label(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false) {
            return $date;
        }

        return $parsed->format('d M');
    }
}
