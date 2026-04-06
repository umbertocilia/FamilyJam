<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Authorization\Permission;
use App\Authorization\SystemRole;
use App\Models\Audit\AuditLogModel;
use App\Models\Authorization\PermissionModel;
use App\Models\Authorization\RoleModel;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DomainException;

final class RoleManagementService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?RoleModel $roleModel = null,
        private readonly ?PermissionModel $permissionModel = null,
        private readonly ?RolePermissionModel $rolePermissionModel = null,
        private readonly ?MembershipRoleModel $membershipRoleModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array{membership: array<string, mixed>, roles: list<array<string, mixed>>, permissions: array<string, list<array<string, mixed>>>}|null
     */
    public function indexContext(int $actorUserId, string $identifier): ?array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $roleModel = $this->roleModel ?? new RoleModel();
        $rolePermissionModel = $this->rolePermissionModel ?? new RolePermissionModel();
        $roles = $roleModel->listForHousehold((int) $membership['household_id']);

        foreach ($roles as &$role) {
            $role['permission_codes'] = $rolePermissionModel->findPermissionCodesByRoleId((int) $role['id']);
        }
        unset($role);

        return [
            'membership' => $membership,
            'roles' => $roles,
            'permissions' => $this->permissionCatalog(),
        ];
    }

    /**
     * @return array{membership: array<string, mixed>, role: array<string, mixed>, permissions: list<array<string, mixed>>, permission_catalog: array<string, list<array<string, mixed>>>}|null
     */
    public function roleDetail(int $actorUserId, string $identifier, int $roleId): ?array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $role = ($this->roleModel ?? new RoleModel())->findForHousehold((int) $membership['household_id'], $roleId);

        if ($role === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'role' => $role,
            'permissions' => ($this->rolePermissionModel ?? new RolePermissionModel())->findPermissionDetailsByRoleId($roleId),
            'permission_catalog' => $this->permissionCatalog(),
        ];
    }

    /**
     * @return array{membership: array<string, mixed>, role: array<string, mixed>|null, selected_permissions: list<string>, permission_catalog: array<string, list<array<string, mixed>>>}|null
     */
    public function roleFormContext(int $actorUserId, string $identifier, ?int $roleId = null): ?array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $role = null;
        $selectedPermissions = [];

        if ($roleId !== null) {
            $role = ($this->roleModel ?? new RoleModel())->findForHousehold((int) $membership['household_id'], $roleId);

            if ($role === null || ! empty($role['is_system'])) {
                return null;
            }

            $selectedPermissions = ($this->rolePermissionModel ?? new RolePermissionModel())->findPermissionCodesByRoleId($roleId);
        }

        return [
            'membership' => $membership,
            'role' => $role,
            'selected_permissions' => $selectedPermissions,
            'permission_catalog' => $this->permissionCatalog(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createRole(int $actorUserId, string $identifier, array $payload): array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            throw new DomainException('Permessi insufficienti per creare ruoli.');
        }

        $db = $this->db ?? Database::connect();
        $roleModel = $this->roleModel ?? new RoleModel($db);
        $permissionModel = $this->permissionModel ?? new PermissionModel($db);
        $rolePermissionModel = $this->rolePermissionModel ?? new RolePermissionModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $code = strtolower(trim((string) ($payload['code'] ?? '')));
        $this->guardRolePayload((int) $membership['household_id'], $code, null);
        $permissionCodes = $this->normalizePermissionCodes($payload['permission_codes'] ?? []);
        $permissionIds = $permissionModel->findIdsByCodes($permissionCodes);

        if (count($permissionIds) !== count($permissionCodes)) {
            throw new DomainException('Uno o piu permessi selezionati non sono validi.');
        }

        $db->transException(true)->transStart();

        $roleId = $roleModel->insert([
            'household_id' => (int) $membership['household_id'],
            'scope_household_id' => (int) $membership['household_id'],
            'code' => $code,
            'name' => trim((string) $payload['name']),
            'description' => $this->nullableString($payload['description'] ?? null),
            'is_system' => 0,
            'is_assignable' => 1,
        ], true);

        $rolePermissionModel->syncPermissionIds((int) $roleId, array_values($permissionIds));

        $auditLogService->record(
            action: 'role.created',
            entityType: 'role',
            entityId: (int) $roleId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            after: $this->roleSnapshot((int) $roleId, $roleModel, $rolePermissionModel),
        );

        $db->transComplete();

        return $roleModel->find((int) $roleId) ?? [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateRole(int $actorUserId, string $identifier, int $roleId, array $payload): array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            throw new DomainException('Permessi insufficienti per aggiornare ruoli.');
        }

        $db = $this->db ?? Database::connect();
        $roleModel = $this->roleModel ?? new RoleModel($db);
        $permissionModel = $this->permissionModel ?? new PermissionModel($db);
        $rolePermissionModel = $this->rolePermissionModel ?? new RolePermissionModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $role = $roleModel->findForHousehold((int) $membership['household_id'], $roleId);

        if ($role === null || ! empty($role['is_system'])) {
            throw new DomainException('Il ruolo richiesto non e modificabile.');
        }

        $before = $this->roleSnapshot($roleId, $roleModel, $rolePermissionModel);

        $code = strtolower(trim((string) ($payload['code'] ?? '')));
        $this->guardRolePayload((int) $membership['household_id'], $code, $roleId);
        $permissionCodes = $this->normalizePermissionCodes($payload['permission_codes'] ?? []);
        $permissionIds = $permissionModel->findIdsByCodes($permissionCodes);

        if (count($permissionIds) !== count($permissionCodes)) {
            throw new DomainException('Uno o piu permessi selezionati non sono validi.');
        }

        $db->transException(true)->transStart();

        $roleModel->update($roleId, [
            'scope_household_id' => (int) $membership['household_id'],
            'name' => trim((string) $payload['name']),
            'code' => $code,
            'description' => $this->nullableString($payload['description'] ?? null),
        ]);

        $rolePermissionModel->syncPermissionIds($roleId, array_values($permissionIds));

        $auditLogService->record(
            action: 'role.updated',
            entityType: 'role',
            entityId: $roleId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            before: $before,
            after: $this->roleSnapshot($roleId, $roleModel, $rolePermissionModel),
        );

        $db->transComplete();

        return $roleModel->find($roleId) ?? [];
    }

    /**
     * @return array{membership: array<string, mixed>, target_membership: array<string, mixed>, assignable_roles: list<array<string, mixed>>, locked_roles: list<array<string, mixed>>, selected_role_ids: list<int>}|null
     */
    public function membershipAssignmentContext(int $actorUserId, string $identifier, int $membershipId): ?array
    {
        $membership = $this->authorizedMembership($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $targetMembership = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->findMembershipDetail((int) $membership['household_id'], $membershipId);

        if ($targetMembership === null) {
            return null;
        }

        $currentRoles = ($this->membershipRoleModel ?? new MembershipRoleModel())->listRolesForMembership($membershipId);
        $assignableRoles = ($this->roleModel ?? new RoleModel())->findAssignableForHousehold((int) $membership['household_id']);
        $lockedRoles = array_values(array_filter(
            $currentRoles,
            static fn (array $role): bool => (int) ($role['is_assignable'] ?? 0) !== 1,
        ));

        return [
            'membership' => $membership,
            'target_membership' => $targetMembership,
            'assignable_roles' => $assignableRoles,
            'locked_roles' => $lockedRoles,
            'selected_role_ids' => ($this->membershipRoleModel ?? new MembershipRoleModel())->findRoleIdsByMembershipId($membershipId),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assignRolesToMembership(int $actorUserId, string $identifier, int $membershipId, array $payload): void
    {
        $context = $this->membershipAssignmentContext($actorUserId, $identifier, $membershipId);

        if ($context === null) {
            throw new DomainException('Membership non disponibile per l\'assegnazione ruoli.');
        }

        $db = $this->db ?? Database::connect();
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $membershipRoleModel = $this->membershipRoleModel ?? new MembershipRoleModel($db);
        $roleIds = array_values(array_unique(array_map('intval', (array) ($payload['role_ids'] ?? []))));

        $lockedRoleIds = array_values(array_map(
            static fn (array $role): int => (int) $role['id'],
            $context['locked_roles'],
        ));

        if ($roleIds === [] && $lockedRoleIds === []) {
            throw new DomainException('Seleziona almeno un ruolo assegnabile.');
        }

        $allowedRoles = [];

        foreach ($context['assignable_roles'] as $role) {
            $allowedRoles[(int) $role['id']] = $role;
        }

        foreach ($roleIds as $roleId) {
            if (! array_key_exists($roleId, $allowedRoles)) {
                throw new DomainException('Uno o piu ruoli selezionati non sono assegnabili in questa household.');
            }
        }

        $finalRoleIds = array_values(array_unique(array_merge($lockedRoleIds, $roleIds)));
        $before = $this->membershipRoleSnapshot($membershipId, $membershipRoleModel);

        $db->transException(true)->transStart();

        $membershipRoleModel->syncRoles($membershipId, $finalRoleIds, $actorUserId);

        $auditLogService->record(
            action: 'membership.roles_updated',
            entityType: 'household_membership',
            entityId: $membershipId,
            actorUserId: $actorUserId,
            householdId: (int) $context['membership']['household_id'],
            before: $before,
            after: $this->membershipRoleSnapshot($membershipId, $membershipRoleModel),
        );

        $db->transComplete();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function permissionCatalog(): array
    {
        $catalog = [];

        foreach (($this->permissionModel ?? new PermissionModel())->listAllOrdered() as $permission) {
            $catalog[(string) $permission['module']][] = $permission;
        }

        ksort($catalog);

        return $catalog;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authorizedMembership(int $actorUserId, string $identifier): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($actorUserId, $identifier, Permission::MANAGE_ROLES)) {
            return null;
        }

        return $membership;
    }

    private function guardRolePayload(int $householdId, string $code, ?int $ignoreRoleId): void
    {
        if ($code === '') {
            throw new DomainException('Il codice ruolo e obbligatorio.');
        }

        if (in_array($code, SystemRole::all(), true)) {
            throw new DomainException('I codici dei ruoli di sistema sono riservati.');
        }

        if (($this->roleModel ?? new RoleModel())->codeExistsForHousehold($householdId, $code, $ignoreRoleId)) {
            throw new DomainException('Esiste gia un ruolo con questo codice nella household corrente.');
        }
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizePermissionCodes(mixed $value): array
    {
        $codes = array_map(
            static fn (mixed $item): string => strtolower(trim((string) $item)),
            is_array($value) ? $value : [],
        );

        return array_values(array_unique(array_filter($codes)));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function roleSnapshot(int $roleId, RoleModel $roleModel, RolePermissionModel $rolePermissionModel): array
    {
        $role = $roleModel->find($roleId);

        if ($role === null) {
            return [];
        }

        $role['permission_codes'] = $rolePermissionModel->findPermissionCodesByRoleId($roleId);

        return $role;
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipRoleSnapshot(int $membershipId, MembershipRoleModel $membershipRoleModel): array
    {
        return [
            'role_ids' => $membershipRoleModel->findRoleIdsByMembershipId($membershipId),
            'role_codes' => $membershipRoleModel->findRoleCodesByMembershipId($membershipId),
        ];
    }
}
