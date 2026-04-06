<?php

declare(strict_types=1);

use App\Services\Recurring\RecurringScheduleService;
use CodeIgniter\Test\CIUnitTestCase;
use DateTimeImmutable;

/**
 * @internal
 */
final class RecurringScheduleServiceTest extends CIUnitTestCase
{
    public function testDailyRecurringRespectsIntervalValue(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'daily',
            'interval_value' => 2,
            'starts_at' => '2026-04-03 09:15:00',
            'ends_at' => null,
        ];

        $nextRunAt = $service->nextRunAt($rule, new DateTimeImmutable('2026-04-03 09:15:00'));

        $this->assertNotNull($nextRunAt);
        $this->assertSame('2026-04-05 09:15:00', $nextRunAt->format('Y-m-d H:i:s'));
    }

    public function testWeeklyRecurringUsesConfiguredWeekdaysDeterministically(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'weekly',
            'interval_value' => 1,
            'starts_at' => '2026-04-01 08:00:00',
            'ends_at' => null,
            'by_weekday' => json_encode([1, 4], JSON_THROW_ON_ERROR),
        ];

        $nextAfterWednesday = $service->nextRunAt($rule, new DateTimeImmutable('2026-04-01 08:00:00'));
        $nextAfterThursday = $service->nextRunAt($rule, new DateTimeImmutable('2026-04-02 08:00:00'));

        $this->assertNotNull($nextAfterWednesday);
        $this->assertNotNull($nextAfterThursday);
        $this->assertSame('2026-04-02 08:00:00', $nextAfterWednesday->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-06 08:00:00', $nextAfterThursday->format('Y-m-d H:i:s'));
    }

    public function testMonthlyRecurringClampsToLastDayOfShorterMonth(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'monthly',
            'interval_value' => 1,
            'starts_at' => '2026-01-31 10:00:00',
            'ends_at' => null,
            'day_of_month' => 31,
        ];

        $nextRunAt = $service->nextRunAt($rule, new DateTimeImmutable('2026-01-31 10:00:00'));

        $this->assertNotNull($nextRunAt);
        $this->assertSame('2026-02-28 10:00:00', $nextRunAt->format('Y-m-d H:i:s'));
    }

    public function testYearlyRecurringClampsLeapDayToLastAvailableDay(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'yearly',
            'interval_value' => 1,
            'starts_at' => '2024-02-29 07:30:00',
            'ends_at' => null,
        ];

        $nextRunAt = $service->nextRunAt($rule, new DateTimeImmutable('2024-02-29 07:30:00'));

        $this->assertNotNull($nextRunAt);
        $this->assertSame('2025-02-28 07:30:00', $nextRunAt->format('Y-m-d H:i:s'));
    }

    public function testCustomRecurringSupportsWeekIntervals(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'custom',
            'interval_value' => 2,
            'starts_at' => '2026-04-01 08:45:00',
            'ends_at' => null,
            'config_json' => json_encode([
                'schedule' => [
                    'custom_unit' => 'week',
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $nextRunAt = $service->nextRunAt($rule, new DateTimeImmutable('2026-04-01 08:45:00'));

        $this->assertNotNull($nextRunAt);
        $this->assertSame('2026-04-15 08:45:00', $nextRunAt->format('Y-m-d H:i:s'));
    }

    public function testDueDatesUpToStopsAtEndsAtBoundary(): void
    {
        $service = new RecurringScheduleService();
        $rule = [
            'frequency' => 'daily',
            'interval_value' => 1,
            'starts_at' => '2026-04-03 09:00:00',
            'ends_at' => '2026-04-05 09:00:00',
            'next_run_at' => '2026-04-03 09:00:00',
        ];

        $dates = $service->dueDatesUpTo($rule, new DateTimeImmutable('2026-04-10 09:00:00'));

        $this->assertCount(3, $dates);
        $this->assertSame('2026-04-03 09:00:00', $dates[0]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-04 09:00:00', $dates[1]->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-05 09:00:00', $dates[2]->format('Y-m-d H:i:s'));
    }
}
