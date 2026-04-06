<?php

declare(strict_types=1);

namespace App\Controllers\Web\Roles;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class MembershipRoleController extends BaseController
{
    public function edit(string $identifier, int $membershipId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('roleManager')->membershipAssignmentContext($this->currentUserId, $identifier, $membershipId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('roles/assign_membership', [
            'pageClass' => 'roles-page',
            'pageTitle' => 'Assign Membership Roles | FamilyJam',
            'assignmentContext' => $context,
        ]);
    }

    public function update(string $identifier, int $membershipId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = [
            'role_ids' => $this->request->getPost('role_ids') ?? [],
        ];

        if (! $this->validateData($payload, config('Validation')->membershipRoleAssignment)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('roleManager')->assignRolesToMembership($this->currentUserId, $identifier, $membershipId, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->to(route_url('memberships.show', $identifier, $membershipId))
            ->with('success', 'Ruoli membership aggiornati.');
    }
}
