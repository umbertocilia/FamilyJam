<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class ProcessRecurringExpensesCommand extends BaseCommand
{
    protected $group = 'Recurring';
    protected $name = 'recurring:expenses-run';
    protected $description = 'Process due recurring expense rules and generate missing expense occurrences.';

    public function run(array $params): void
    {
        $summary = service('recurringExpenseExecutor')->runDue();

        CLI::write('Recurring expenses processed.', 'green');
        CLI::write('Rules processed: ' . $summary['processed_rules']);
        CLI::write('Expenses generated: ' . $summary['generated_expenses']);
        CLI::write('Duplicates skipped: ' . $summary['skipped_duplicates']);
        CLI::write('Rules disabled: ' . $summary['disabled_rules']);
        CLI::write('Anomalies: ' . $summary['anomalies']);
    }
}
