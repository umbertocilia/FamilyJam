<?php

declare(strict_types=1);

namespace App\Controllers\Web\Expenses;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class ExpenseController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $filters = $this->request->getGet([
            'category_id',
            'expense_group_id',
            'month',
            'member_id',
            'status',
        ]);

        if (! $this->validateData($filters, config('Validation')->expenseFilters)) {
            $filters = [];
        }

        $context = service('expenseService')->listContext($this->currentUserId, $identifier, $filters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('expenses/index', [
            'pageClass' => 'expenses-page',
            'pageTitle' => 'Expenses | FamilyJam',
            'expenseListContext' => $context,
            'canCreateExpense' => $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::CREATE_EXPENSE),
        ]);
    }

    public function show(string $identifier, int $expenseId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('expenseService')->detailContext($this->currentUserId, $identifier, $expenseId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('expenses/show', [
            'pageClass' => 'expenses-page',
            'pageTitle' => 'Expense Detail | FamilyJam',
            'expenseDetailContext' => $context,
            'canEditExpense' => $this->householdAuthorization->canManage($this->currentUserId, $identifier, 'edit_expense', $context['expense']),
            'canDeleteExpense' => $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::DELETE_EXPENSE)
                && $context['expense']['deleted_at'] === null,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('expenseService')->formContext($this->currentUserId, $identifier);

        if ($context === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::CREATE_EXPENSE)) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('expenses/form', [
            'pageClass' => 'expenses-page',
            'pageTitle' => 'Create Expense | FamilyJam',
            'expenseFormContext' => $context,
            'formMode' => 'create',
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'title',
            'description',
            'expense_date',
            'currency',
            'total_amount',
            'category_id',
            'expense_group_id',
            'split_method',
        ]);
        $payload['payers'] = $this->request->getPost('payers') ?? [];
        $payload['splits'] = $this->request->getPost('splits') ?? [];

        if (! $this->validateData($payload, config('Validation')->expenseCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $expense = service('expenseService')->create($this->currentUserId, $identifier, $payload, $this->request->getFile('receipt_attachment'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('expenses.show', $identifier, $expense['id']))
            ->with('success', ui_locale() === 'it' ? 'Spesa creata correttamente.' : 'Expense created successfully.');
    }

    public function edit(string $identifier, int $expenseId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('expenseService')->formContext($this->currentUserId, $identifier, $expenseId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('expenses/form', [
            'pageClass' => 'expenses-page',
            'pageTitle' => 'Edit Expense | FamilyJam',
            'expenseFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $expenseId): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'title',
            'description',
            'expense_date',
            'currency',
            'total_amount',
            'category_id',
            'expense_group_id',
            'split_method',
        ]);
        $payload['payers'] = $this->request->getPost('payers') ?? [];
        $payload['splits'] = $this->request->getPost('splits') ?? [];

        if (! $this->validateData($payload, config('Validation')->expenseUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $expense = service('expenseService')->update($this->currentUserId, $identifier, $expenseId, $payload, $this->request->getFile('receipt_attachment'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('expenses.show', $identifier, $expense['id']))
            ->with('success', ui_locale() === 'it' ? 'Spesa aggiornata.' : 'Expense updated.');
    }

    public function delete(string $identifier, int $expenseId): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('expenseService')->softDelete($this->currentUserId, $identifier, $expenseId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('expenses.index', $identifier))
            ->with('success', ui_locale() === 'it' ? 'Spesa eliminata.' : 'Expense deleted.');
    }

    public function receipt(string $identifier, int $expenseId): DownloadResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('expenseService')->receiptContext($this->currentUserId, $identifier, $expenseId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        $path = service('attachmentStorage')->absolutePath($context['attachment']);

        return $this->response
            ->download($path, null)
            ->setFileName((string) $context['attachment']['original_name']);
    }
}
