<?php

declare(strict_types=1);

namespace App\Controllers\Web\Shopping;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class ShoppingListController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('shoppingListService')->indexContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('shopping/index', [
            'pageClass' => 'shopping-page',
            'pageTitle' => 'Shopping Lists | FamilyJam',
            'shoppingIndexContext' => $context,
        ]);
    }

    public function show(string $identifier, int $listId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('shoppingListService')->detailContext($this->currentUserId, $identifier, $listId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('shopping/show', [
            'pageClass' => 'shopping-page',
            'pageTitle' => 'Shopping List | FamilyJam',
            'shoppingDetailContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_SHOPPING)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('shoppingListService')->formContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('shopping/form', [
            'pageClass' => 'shopping-page',
            'pageTitle' => 'Create Shopping List | FamilyJam',
            'shoppingFormContext' => $context,
            'formMode' => 'create',
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['name', 'is_default']);

        if (! $this->validateData($payload, config('Validation')->shoppingListCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $list = service('shoppingListService')->create($this->currentUserId, $identifier, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $list['id']))->with('success', 'Shopping list creata.');
    }

    public function edit(string $identifier, int $listId): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_SHOPPING)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('shoppingListService')->formContext($this->currentUserId, $identifier, $listId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('shopping/form', [
            'pageClass' => 'shopping-page',
            'pageTitle' => 'Edit Shopping List | FamilyJam',
            'shoppingFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $listId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost(['name', 'is_default']);

        if (! $this->validateData($payload, config('Validation')->shoppingListUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('shoppingListService')->update($this->currentUserId, $identifier, $listId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.show', $identifier, $listId))->with('success', 'Shopping list aggiornata.');
    }

    public function delete(string $identifier, int $listId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('shoppingListService')->softDelete($this->currentUserId, $identifier, $listId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('shopping.index', $identifier))->with('success', 'Shopping list eliminata.');
    }
}
