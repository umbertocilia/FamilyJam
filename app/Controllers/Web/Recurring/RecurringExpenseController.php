<?php

declare(strict_types=1);

namespace App\Controllers\Web\Recurring;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class RecurringExpenseController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('recurringExpenseService')->listContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('recurring/index', [
            'pageClass' => 'recurring-page',
            'pageTitle' => 'Recurring Expenses | FamilyJam',
            'recurringContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        if (! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::CREATE_EXPENSE)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('recurringExpenseService')->formContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('recurring/form', [
            'pageClass' => 'recurring-page',
            'pageTitle' => 'Create Recurring Expense | FamilyJam',
            'recurringFormContext' => $context,
            'formMode' => 'create',
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'title',
            'description',
            'currency',
            'total_amount',
            'category_id',
            'split_method',
            'frequency',
            'interval_value',
            'starts_at',
            'ends_at',
            'day_of_month',
            'custom_unit',
        ]);
        $payload['by_weekday'] = $this->request->getPost('by_weekday') ?? [];
        $payload['payers'] = $this->request->getPost('payers') ?? [];
        $payload['splits'] = $this->request->getPost('splits') ?? [];

        if (! $this->validateData($payload, config('Validation')->recurringExpenseCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('recurringExpenseService')->create($this->currentUserId, $identifier, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('recurring.index', $identifier))
            ->with('success', 'Recurring expense creata correttamente.');
    }

    public function edit(string $identifier, int $ruleId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('recurringExpenseService')->formContext($this->currentUserId, $identifier, $ruleId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('recurring/form', [
            'pageClass' => 'recurring-page',
            'pageTitle' => 'Edit Recurring Expense | FamilyJam',
            'recurringFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $ruleId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'title',
            'description',
            'currency',
            'total_amount',
            'category_id',
            'split_method',
            'frequency',
            'interval_value',
            'starts_at',
            'ends_at',
            'day_of_month',
            'custom_unit',
        ]);
        $payload['by_weekday'] = $this->request->getPost('by_weekday') ?? [];
        $payload['payers'] = $this->request->getPost('payers') ?? [];
        $payload['splits'] = $this->request->getPost('splits') ?? [];

        if (! $this->validateData($payload, config('Validation')->recurringExpenseUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('recurringExpenseService')->update($this->currentUserId, $identifier, $ruleId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('recurring.index', $identifier))
            ->with('success', 'Recurring expense aggiornata.');
    }

    public function disable(string $identifier, int $ruleId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('recurringExpenseService')->disable($this->currentUserId, $identifier, $ruleId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('recurring.index', $identifier))
            ->with('success', 'Recurring expense disattivata.');
    }
}
