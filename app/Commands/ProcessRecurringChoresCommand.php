<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class ProcessRecurringChoresCommand extends BaseCommand
{
    protected $group = 'Recurring';
    protected $name = 'chores:occurrences-run';
    protected $description = 'Process due recurring chore rules and generate missing chore occurrences.';

    public function run(array $params): void
    {
        $summary = service('choreRecurringExecutor')->runDue();

        CLI::write('Recurring chores processed.', 'green');
        CLI::write('Rules processed: ' . $summary['processed_rules']);
        CLI::write('Occurrences generated: ' . $summary['generated_occurrences']);
        CLI::write('Duplicates skipped: ' . $summary['skipped_duplicates']);
        CLI::write('Rules disabled: ' . $summary['disabled_rules']);
        CLI::write('Anomalies: ' . $summary['anomalies']);
    }
}
