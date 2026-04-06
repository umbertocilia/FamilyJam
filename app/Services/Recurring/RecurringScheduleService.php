<?php

declare(strict_types=1);

namespace App\Services\Recurring;

use DateInterval;
use DateTimeImmutable;
use DomainException;

final class RecurringScheduleService
{
    /**
     * @param array<string, mixed> $rule
     */
    public function firstRunAt(array $rule): DateTimeImmutable
    {
        $startsAt = $this->toDateTime((string) $rule['starts_at']);

        return match ((string) $rule['frequency']) {
            'daily', 'weekly', 'monthly', 'yearly', 'custom' => $startsAt,
            default => throw new DomainException('Unsupported recurring frequency.'),
        };
    }

    /**
     * @param array<string, mixed> $rule
     */
    public function nextRunAt(array $rule, DateTimeImmutable $currentRunAt): ?DateTimeImmutable
    {
        $nextRunAt = match ((string) $rule['frequency']) {
            'daily' => $currentRunAt->add(new DateInterval('P' . $this->intervalValue($rule) . 'D')),
            'weekly' => $this->nextWeeklyRunAt($rule, $currentRunAt),
            'monthly' => $this->nextMonthlyRunAt($rule, $currentRunAt),
            'yearly' => $this->nextYearlyRunAt($rule, $currentRunAt),
            'custom' => $this->nextCustomRunAt($rule, $currentRunAt),
            default => throw new DomainException('Unsupported recurring frequency.'),
        };

        $endsAt = $this->endsAt($rule);

        if ($endsAt !== null && $nextRunAt > $endsAt) {
            return null;
        }

        return $nextRunAt;
    }

    /**
     * @param array<string, mixed> $rule
     * @return list<DateTimeImmutable>
     */
    public function dueDatesUpTo(array $rule, DateTimeImmutable $until, int $limit = 50): array
    {
        $dates = [];
        $current = $this->toNullableDateTime($rule['next_run_at'] ?? null) ?? $this->firstRunAt($rule);

        while ($current <= $until && count($dates) < $limit) {
            $dates[] = $current;
            $next = $this->nextRunAt($rule, $current);

            if ($next === null) {
                break;
            }

            $current = $next;
        }

        return $dates;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function nextWeeklyRunAt(array $rule, DateTimeImmutable $currentRunAt): DateTimeImmutable
    {
        $weekdays = $this->weeklyDays($rule);
        $anchorWeekStart = $this->weekStart($this->toDateTime((string) $rule['starts_at']));
        $candidate = $currentRunAt;

        for ($i = 1; $i <= 366 * 3; $i++) {
            $candidate = $candidate->add(new DateInterval('P1D'));
            $candidateWeekStart = $this->weekStart($candidate);
            $weekOffset = (int) floor(($candidateWeekStart->getTimestamp() - $anchorWeekStart->getTimestamp()) / 604800);

            if ($weekOffset < 0 || $weekOffset % $this->intervalValue($rule) !== 0) {
                continue;
            }

            if (in_array((int) $candidate->format('N'), $weekdays, true)) {
                return $candidate;
            }
        }

        throw new DomainException('Unable to compute next weekly recurring run.');
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function nextMonthlyRunAt(array $rule, DateTimeImmutable $currentRunAt): DateTimeImmutable
    {
        $startsAt = $this->toDateTime((string) $rule['starts_at']);
        $targetDay = $this->dayOfMonth($rule, (int) $startsAt->format('j'));
        $monthsToAdd = $this->intervalValue($rule);
        $targetMonth = $currentRunAt->setDate(
            (int) $currentRunAt->format('Y'),
            (int) $currentRunAt->format('n'),
            1,
        )->add(new DateInterval('P' . $monthsToAdd . 'M'));

        return $this->withDayAndTime($targetMonth, $targetDay, $startsAt);
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function nextYearlyRunAt(array $rule, DateTimeImmutable $currentRunAt): DateTimeImmutable
    {
        $startsAt = $this->toDateTime((string) $rule['starts_at']);
        $interval = $this->intervalValue($rule);
        $targetYear = (int) $currentRunAt->format('Y') + $interval;
        $month = (int) $startsAt->format('n');
        $day = (int) $startsAt->format('j');
        $lastDay = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $targetYear, $month)))->format('t');
        $resolvedDay = min($day, $lastDay);

        return $currentRunAt->setDate($targetYear, $month, $resolvedDay)
            ->setTime((int) $startsAt->format('H'), (int) $startsAt->format('i'), (int) $startsAt->format('s'));
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function nextCustomRunAt(array $rule, DateTimeImmutable $currentRunAt): DateTimeImmutable
    {
        $config = $this->decodeConfig($rule);
        $unit = strtolower((string) ($config['schedule']['custom_unit'] ?? 'day'));
        $interval = $this->intervalValue($rule);

        return match ($unit) {
            'day', 'days' => $currentRunAt->add(new DateInterval('P' . $interval . 'D')),
            'week', 'weeks' => $currentRunAt->add(new DateInterval('P' . ($interval * 7) . 'D')),
            'month', 'months' => $this->nextMonthlyRunAt(array_merge($rule, ['interval_value' => $interval]), $currentRunAt),
            'year', 'years' => $this->nextYearlyRunAt(array_merge($rule, ['interval_value' => $interval]), $currentRunAt),
            default => throw new DomainException('Unsupported custom recurring unit.'),
        };
    }

    /**
     * @param array<string, mixed> $rule
     * @return list<int>
     */
    private function weeklyDays(array $rule): array
    {
        if (! empty($rule['by_weekday'])) {
            $decoded = json_decode((string) $rule['by_weekday'], true);

            if (is_array($decoded) && $decoded !== []) {
                return array_values(array_unique(array_map(static fn (mixed $value): int => (int) $value, $decoded)));
            }
        }

        return [(int) $this->toDateTime((string) $rule['starts_at'])->format('N')];
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function dayOfMonth(array $rule, int $fallback): int
    {
        $day = (int) ($rule['day_of_month'] ?? 0);

        return $day > 0 ? $day : $fallback;
    }

    private function weekStart(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->sub(new DateInterval('P' . ((int) $dateTime->format('N') - 1) . 'D'))->setTime(0, 0, 0);
    }

    private function withDayAndTime(DateTimeImmutable $dateTime, int $day, DateTimeImmutable $template): DateTimeImmutable
    {
        $year = (int) $dateTime->format('Y');
        $month = (int) $dateTime->format('n');
        $lastDay = (int) $dateTime->format('t');
        $resolvedDay = min(max($day, 1), $lastDay);

        return $dateTime->setDate($year, $month, $resolvedDay)
            ->setTime((int) $template->format('H'), (int) $template->format('i'), (int) $template->format('s'));
    }

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    private function decodeConfig(array $rule): array
    {
        $decoded = json_decode((string) ($rule['config_json'] ?? '{}'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function intervalValue(array $rule): int
    {
        return max(1, (int) ($rule['interval_value'] ?? 1));
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function endsAt(array $rule): ?DateTimeImmutable
    {
        return $this->toNullableDateTime($rule['ends_at'] ?? null);
    }

    private function toDateTime(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value);
    }

    private function toNullableDateTime(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return new DateTimeImmutable($value);
    }
}
