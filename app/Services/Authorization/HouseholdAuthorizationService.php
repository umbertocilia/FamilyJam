<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Authorization\Permission;
use App\Authorization\Policies\ExpensePolicy;
use App\Authorization\Policies\HouseholdPolicy;
use App\Authorization\Policies\MembershipPolicy;
use App\Authorization\Policies\RolePolicy;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;

final class HouseholdAuthorizationService
{
    public function __construct(
        private readonly ?HouseholdMembershipModel $membershipModel = null,
        private readonly ?MembershipRoleModel $membershipRoleModel = null,
        private readonly ?RolePermissionModel $rolePermissionModel = null,
    ) {
    }

    /**
     * @return array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>}|null
     */
    public function context(int $userId, int $householdId): ?array
    {
        $membership = $this->membership($userId, $householdId);

        if ($membership === null) {
            return null;
        }

        return $this->buildContext($membership);
    }

    /**
     * @return array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>}|null
     */
    public function contextByIdentifier(int $userId, string $identifier): ?array
    {
        $membership = $this->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        return $this->buildContext($membership);
    }

    public function membership(int $userId, int $householdId): ?array
    {
        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel();

        return $membershipModel->findActiveMembership($householdId, $userId);
    }

    public function membershipBySlug(int $userId, string $householdSlug): ?array
    {
        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel();

        return $membershipModel->findActiveMembershipBySlug($householdSlug, $userId);
    }

    public function membershipByIdentifier(int $userId, string $identifier): ?array
    {
        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel();

        return $membershipModel->findActiveMembershipByIdentifier($identifier, $userId);
    }

    /**
     * @return list<string>
     */
    public function permissionsForMembership(int $membershipId): array
    {
        $membershipRoleModel = $this->membershipRoleModel ?? new MembershipRoleModel();
        $roleIds = $membershipRoleModel->findRoleIdsByMembershipId($membershipId);
        $rolePermissionModel = $this->rolePermissionModel ?? new RolePermissionModel();

        return $rolePermissionModel->findPermissionCodesByRoleIds($roleIds);
    }

    /**
     * @return list<string>
     */
    public function roleCodesForMembership(int $membershipId): array
    {
        return ($this->membershipRoleModel ?? new MembershipRoleModel())
            ->findRoleCodesByMembershipId($membershipId);
    }

    public function hasPermission(int $userId, string $identifier, string $permission): bool
    {
        return $this->canByIdentifier($userId, $identifier, $permission);
    }

    public function hasRole(int $userId, string $identifier, string $roleCode): bool
    {
        $context = $this->contextByIdentifier($userId, $identifier);

        if ($context === null) {
            return false;
        }

        return in_array($roleCode, $context['role_codes'], true);
    }

    public function can(int $userId, int $householdId, string $permission): bool
    {
        $context = $this->context($userId, $householdId);

        if ($context === null) {
            return false;
        }

        return in_array($permission, $context['permissions'], true);
    }

    public function canBySlug(int $userId, string $householdSlug, string $permission): bool
    {
        $membership = $this->membershipBySlug($userId, $householdSlug);

        if ($membership === null) {
            return false;
        }

        return in_array($permission, $this->permissionsForMembership((int) $membership['id']), true);
    }

    public function canByIdentifier(int $userId, string $identifier, string $permission): bool
    {
        $context = $this->contextByIdentifier($userId, $identifier);

        if ($context === null) {
            return false;
        }

        return in_array($permission, $context['permissions'], true);
    }

    /**
     * @param array<string, mixed>|null $resource
     */
    public function canManage(int $userId, string $identifier, string $ability, ?array $resource = null): bool
    {
        $context = $this->contextByIdentifier($userId, $identifier);

        if ($context === null) {
            return false;
        }

        return match ($ability) {
            'edit_expense', 'expense.update' => (new ExpensePolicy())->update($context, $resource),
            Permission::MANAGE_MEMBERS, 'members.manage' => (new MembershipPolicy())->manageMembers($context),
            Permission::MANAGE_SETTINGS, 'settings.manage' => (new HouseholdPolicy())->manageSettings($context),
            Permission::MANAGE_ROLES, 'roles.manage' => (new RolePolicy())->manageRoles($context),
            default => in_array($ability, $context['permissions'], true),
        };
    }

    /**
     * @param array<string, mixed> $membership
     * @return array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>}
     */
    private function buildContext(array $membership): array
    {
        $roleCodes = $this->parseRoleCodes((string) ($membership['role_codes'] ?? ''));

        return [
            'membership' => $membership,
            'permissions' => $this->permissionsForMembership((int) $membership['id']),
            'role_codes' => $roleCodes,
        ];
    }

    /**
     * @return list<string>
     */
    private function parseRoleCodes(string $roleCodes): array
    {
        if (trim($roleCodes) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $roleCodes))));
    }
}
