<?php

declare(strict_types=1);

namespace App\Controllers\Web\Shopping;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class ShoppingItemController extends BaseController
{
    public function store(string $identifier, int $listId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['name', 'quantity', 'unit', 'category', 'notes', 'priority', 'assigned_user_id']);

        if (! $this->validateData($payload, config('Validation')->shoppingItemCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('shoppingItemService')->quickAdd($this->currentUserId, $identifier, $listId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId))->with('success', 'Item aggiunto.');
    }

    public function update(string $identifier, int $itemId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['name', 'quantity', 'unit', 'category', 'notes', 'priority', 'assigned_user_id', 'position']);
        $listId = (int) ($this->request->getPost('shopping_list_id') ?? 0);

        if (! $this->validateData($payload, config('Validation')->shoppingItemUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $item = service('shoppingItemService')->update($this->currentUserId, $identifier, $itemId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId > 0 ? $listId : $item['shopping_list_id']))->with('success', 'Item aggiornato.');
    }

    public function delete(string $identifier, int $itemId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $listId = (int) ($this->request->getPost('shopping_list_id') ?? 0);

        try {
            service('shoppingItemService')->softDelete($this->currentUserId, $identifier, $itemId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId))->with('success', 'Item rimosso.');
    }

    public function togglePurchased(string $identifier, int $itemId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $listId = (int) ($this->request->getPost('shopping_list_id') ?? 0);

        try {
            $item = service('shoppingItemService')->togglePurchased($this->currentUserId, $identifier, $itemId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId > 0 ? $listId : $item['shopping_list_id']))->with('success', 'Stato item aggiornato.');
    }

    public function bulkPurchased(string $identifier, int $listId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['mark_as']);
        $payload['item_ids'] = (array) $this->request->getPost('item_ids');

        if (! $this->validateData($payload, config('Validation')->shoppingBulkPurchase)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('shoppingItemService')->bulkPurchase(
                $this->currentUserId,
                $identifier,
                $listId,
                array_values(array_unique(array_map('intval', (array) $payload['item_ids']))),
                (string) $payload['mark_as'] === 'purchased',
            );
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId))->with('success', 'Aggiornamento batch completato.');
    }

    public function convertToExpense(string $identifier, int $listId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['title', 'total_amount', 'expense_date', 'payer_user_id', 'category_id']);
        $payload['item_ids'] = (array) $this->request->getPost('item_ids');
        $payload['participant_user_ids'] = (array) $this->request->getPost('participant_user_ids');

        if (! $this->validateData($payload, config('Validation')->shoppingConvertExpense)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $expense = service('shoppingConversionService')->convertPurchasedItemsToExpense($this->currentUserId, $identifier, $listId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('expenses.show', $identifier, $expense['id']))->with('success', 'Item convertiti in spesa condivisa.');
    }
}
