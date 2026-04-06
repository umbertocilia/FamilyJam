<?php

declare(strict_types=1);

namespace App\Controllers\Web\Balances;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class SettlementController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('settlementService')->listContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('settlements/index', [
            'pageClass' => 'settlements-page',
            'pageTitle' => 'Settlements | FamilyJam',
            'settlementContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('settlementService')->formContext($this->currentUserId, $identifier);

        if ($context === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::ADD_SETTLEMENT)) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('settlements/form', [
            'pageClass' => 'settlements-page',
            'pageTitle' => 'Create Settlement | FamilyJam',
            'settlementFormContext' => $context,
            'settlementPrefill' => [
                'from_user_id' => $this->request->getGet('from_user_id'),
                'to_user_id' => $this->request->getGet('to_user_id'),
                'expense_group_id' => $this->request->getGet('expense_group_id'),
                'currency' => $this->request->getGet('currency'),
                'amount' => $this->request->getGet('amount'),
            ],
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'from_user_id',
            'to_user_id',
            'expense_group_id',
            'settlement_date',
            'currency',
            'amount',
            'payment_method',
            'note',
        ]);

        if (! $this->validateData($payload, config('Validation')->settlementCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('settlementService')->create($this->currentUserId, $identifier, $payload, $this->request->getFile('attachment'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('settlements.index', $identifier))
            ->with('success', ui_locale() === 'it' ? 'Rimborso registrato correttamente.' : 'Settlement recorded successfully.');
    }

    public function attachment(string $identifier, int $settlementId): DownloadResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('settlementService')->attachmentContext($this->currentUserId, $identifier, $settlementId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        $path = service('attachmentStorage')->absolutePath($context['attachment']);

        return $this->response
            ->download($path, null)
            ->setFileName((string) $context['attachment']['original_name']);
    }
}
