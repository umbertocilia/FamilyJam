<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class ProcessChoreRemindersCommand extends BaseCommand
{
    protected $group = 'Recurring';
    protected $name = 'chores:reminders-run';
    protected $description = 'Mark due chore reminders and sync overdue statuses.';

    public function run(array $params): void
    {
        $summary = service('choreReminderService')->run();

        CLI::write('Chore reminders processed.', 'green');
        CLI::write('Overdue synced: ' . $summary['overdue_synced']);
        CLI::write('Reminders marked: ' . $summary['reminders_marked']);
    }
}
