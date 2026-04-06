<?php

declare(strict_types=1);

namespace App\Controllers\Web\Roles;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class RoleController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('roleManager')->indexContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('roles/index', [
            'pageClass' => 'roles-page',
            'pageTitle' => 'Roles | FamilyJam',
            'roleContext' => $context,
        ]);
    }

    public function show(string $identifier, int $roleId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('roleManager')->roleDetail($this->currentUserId, $identifier, $roleId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('roles/show', [
            'pageClass' => 'roles-page',
            'pageTitle' => 'Role Detail | FamilyJam',
            'roleDetailContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('roleManager')->roleFormContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('roles/form', [
            'pageClass' => 'roles-page',
            'pageTitle' => 'Create Role | FamilyJam',
            'roleFormContext' => $context,
            'formMode' => 'create',
        ]);
    }

    public function store(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = [
            'name' => $this->request->getPost('name'),
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'permission_codes' => $this->request->getPost('permission_codes') ?? [],
        ];

        if (! $this->validateData($payload, config('Validation')->customRoleCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $role = service('roleManager')->createRole($this->currentUserId, $identifier, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('roles.show', $identifier, $role['id']))
            ->with('success', 'Ruolo custom creato.');
    }

    public function edit(string $identifier, int $roleId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('roleManager')->roleFormContext($this->currentUserId, $identifier, $roleId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('roles/form', [
            'pageClass' => 'roles-page',
            'pageTitle' => 'Edit Role | FamilyJam',
            'roleFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $roleId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = [
            'name' => $this->request->getPost('name'),
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'permission_codes' => $this->request->getPost('permission_codes') ?? [],
        ];

        if (! $this->validateData($payload, config('Validation')->customRoleUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $role = service('roleManager')->updateRole($this->currentUserId, $identifier, $roleId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('roles.show', $identifier, $role['id']))
            ->with('success', 'Ruolo aggiornato.');
    }
}
