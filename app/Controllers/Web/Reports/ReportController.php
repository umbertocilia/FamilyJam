<?php

declare(strict_types=1);

namespace App\Controllers\Web\Reports;

use App\Controllers\BaseController;
use CodeIgniter\Security\Exceptions\SecurityException;

final class ReportController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $expenseFilters = $this->request->getGet(['months', 'member_id']);
        $choreFilters = $this->request->getGet(['days', 'assigned_user_id']);

        if (! $this->validateData($expenseFilters, config('Validation')->reportExpenseFilters)) {
            $expenseFilters = [];
        }

        if (! $this->validateData($choreFilters, config('Validation')->reportChoreFilters)) {
            $choreFilters = [];
        }

        $context = service('reportService')->indexContext($this->currentUserId, $identifier, $expenseFilters, $choreFilters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('reports/index', [
            'pageClass' => 'reports-page',
            'pageTitle' => 'Reports | FamilyJam',
            'reportContext' => $context,
        ]);
    }

    public function expenses(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $filters = $this->request->getGet(['months', 'member_id']);

        if (! $this->validateData($filters, config('Validation')->reportExpenseFilters)) {
            $filters = [];
        }

        $context = service('reportService')->expenseReportContext($this->currentUserId, $identifier, $filters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('reports/expenses', [
            'pageClass' => 'reports-page',
            'pageTitle' => 'Expense Reports | FamilyJam',
            'reportContext' => $context,
        ]);
    }

    public function chores(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $filters = $this->request->getGet(['days', 'assigned_user_id']);

        if (! $this->validateData($filters, config('Validation')->reportChoreFilters)) {
            $filters = [];
        }

        $context = service('reportService')->choreReportContext($this->currentUserId, $identifier, $filters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('reports/chores', [
            'pageClass' => 'reports-page',
            'pageTitle' => 'Chore Reports | FamilyJam',
            'reportContext' => $context,
        ]);
    }
}
