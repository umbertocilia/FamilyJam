<?php

declare(strict_types=1);

namespace App\Controllers\Web\Households;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class InvitationController extends BaseController
{
    public function index(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('householdMemberships')->listForHousehold($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        $canManageMembers = $this->householdAuthorization
            ->canByIdentifier($this->currentUserId, $identifier, Permission::MANAGE_MEMBERS);

        $assignableRoles = $canManageMembers
            ? (new \App\Models\Authorization\RoleModel())->findAssignableForHousehold((int) $context['membership']['household_id'])
            : [];

        return $this->render('invitations/index', [
            'pageClass' => 'memberships-page',
            'pageTitle' => 'Invitations | FamilyJam',
            'membershipContext' => $context,
            'pendingInvitations' => service('householdInvitation')->pendingForHousehold($this->currentUserId, $identifier),
            'assignableRoles' => $assignableRoles,
            'canManageMembers' => $canManageMembers,
        ]);
    }

    public function create(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'email',
            'role_code',
            'message',
        ]);

        if (! $this->validateData($payload, config('Validation')->memberInvitation)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('householdInvitation')->create($this->currentUserId, $identifier, $payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Invito creato e mail scaffolding registrata.');
    }

    public function revoke(string $identifier, int $invitationId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        try {
            service('householdInvitation')->revoke($this->currentUserId, $identifier, $invitationId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Invito revocato.');
    }

    public function accept(string $token): string
    {
        $preview = service('householdInvitation')->preview($token);

        if ($this->currentUserId === null && $preview !== null) {
            $this->session->set('auth.pending_invitation_token', $token);
        }

        return $this->render('invitations/accept', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Accept Invitation | FamilyJam',
            'invitationPreview' => $preview,
            'inviteToken' => $token,
            'inviteEmailMatchesCurrentUser' => $preview !== null
                && $this->currentUser !== null
                && strtolower((string) $preview['invitation']['email']) === strtolower((string) $this->currentUser['email']),
        ]);
    }

    public function acceptSubmit(string $token): RedirectResponse
    {
        if ($this->currentUserId === null) {
            $this->session->set('auth.pending_invitation_token', $token);

            return redirect()->to(route_url('auth.login') . '?invite=' . urlencode($token));
        }

        try {
            $membership = service('householdInvitation')->accept($token, $this->currentUserId);
        } catch (DomainException $exception) {
            return redirect()->to(route_url('invitations.accept', $token))->with('error', $exception->getMessage());
        }

        $this->session->remove('auth.pending_invitation_token');

        if ($membership === null) {
            return redirect()->to(route_url('households.index'))->with('error', 'Invito non valido o scaduto.');
        }

        return redirect()
            ->to(route_url('households.dashboard', $membership['household_slug']))
            ->with('success', 'Invito accettato.');
    }
}
