<?php

declare(strict_types=1);

namespace App\Controllers\Web\Households;

use App\Controllers\BaseController;
use App\Authorization\Permission;
use CodeIgniter\Security\Exceptions\SecurityException;

final class MembershipController extends BaseController
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

        $canManageMembers = $this->householdAuthorization->canManage($this->currentUserId, $identifier, Permission::MANAGE_MEMBERS);
        $canManageRoles = $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_ROLES);
        $assignableRoles = $canManageMembers
            ? (new \App\Models\Authorization\RoleModel())->findAssignableForHousehold((int) $context['membership']['household_id'])
            : [];

        return $this->render('memberships/index', [
            'pageClass' => 'memberships-page',
            'pageTitle' => 'Members | FamilyJam',
            'membershipContext' => $context,
            'pendingInvitations' => service('householdInvitation')->pendingForHousehold($this->currentUserId, $identifier),
            'assignableRoles' => $assignableRoles,
            'canManageMembers' => $canManageMembers,
            'canManageRoles' => $canManageRoles,
        ]);
    }

    public function show(string $identifier, int $membershipId): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('householdMemberships')->detailForHousehold($this->currentUserId, $identifier, $membershipId);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('memberships/show', [
            'pageClass' => 'memberships-page',
            'pageTitle' => 'Membership Detail | FamilyJam',
            'membershipDetailContext' => $context,
            'canManageRoles' => $this->householdAuthorization->hasPermission($this->currentUserId, $identifier, Permission::MANAGE_ROLES),
        ]);
    }
}
