<?php

declare(strict_types=1);

namespace App\Controllers\Web\Chores;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class ChoreController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreService')->overviewContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/index', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Chores | FamilyJam',
            'choreOverviewContext' => $context,
        ]);
    }

    public function templates(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $filters = $this->request->getGet(['assignment_mode', 'is_active']);
        $context = service('choreService')->templatesContext($this->currentUserId, $identifier, $filters);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/templates', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Chore Templates | FamilyJam',
            'choreTemplateContext' => $context,
        ]);
    }

    public function create(string $identifier): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_CHORES)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreService')->formContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/form', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Create Chore | FamilyJam',
            'choreFormContext' => $context,
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
            'assignment_mode',
            'fixed_assignee_user_id',
            'rotation_anchor_user_id',
            'points',
            'estimated_minutes',
            'is_active',
            'recurring_enabled',
            'frequency',
            'interval_value',
            'starts_at',
            'ends_at',
            'day_of_month',
            'custom_unit',
            'first_due_at',
        ]);
        $payload['by_weekday'] = $this->request->getPost('by_weekday') ?? [];

        if (! $this->validateData($payload, config('Validation')->choreTemplateCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('choreService')->create($this->currentUserId, $identifier, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('chores.templates', $identifier))->with('success', 'Template chore creato.');
    }

    public function edit(string $identifier, int $choreId): string
    {
        if ($this->currentUserId === null || ! $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_CHORES)) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('choreService')->formContext($this->currentUserId, $identifier, $choreId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('chores/form', [
            'pageClass' => 'chores-page',
            'pageTitle' => 'Edit Chore | FamilyJam',
            'choreFormContext' => $context,
            'formMode' => 'edit',
        ]);
    }

    public function update(string $identifier, int $choreId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'title',
            'description',
            'assignment_mode',
            'fixed_assignee_user_id',
            'rotation_anchor_user_id',
            'points',
            'estimated_minutes',
            'is_active',
            'recurring_enabled',
            'frequency',
            'interval_value',
            'starts_at',
            'ends_at',
            'day_of_month',
            'custom_unit',
            'first_due_at',
        ]);
        $payload['by_weekday'] = $this->request->getPost('by_weekday') ?? [];

        if (! $this->validateData($payload, config('Validation')->choreTemplateUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('choreService')->update($this->currentUserId, $identifier, $choreId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('chores.templates', $identifier))->with('success', 'Template chore aggiornato.');
    }

    public function toggle(string $identifier, int $choreId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('choreService')->toggleActive($this->currentUserId, $identifier, $choreId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('chores.templates', $identifier))->with('success', 'Stato chore aggiornato.');
    }
}
