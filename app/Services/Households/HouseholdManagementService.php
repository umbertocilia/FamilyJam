<?php

declare(strict_types=1);

namespace App\Services\Households;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Finance\ExpenseGroupMemberModel;
use App\Models\Finance\ExpenseGroupModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class HouseholdManagementService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdProvisioningService $householdProvisioningService = null,
        private readonly ?HouseholdContextService $householdContextService = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?HouseholdSettingModel $householdSettingModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?ExpenseCategoryModel $expenseCategoryModel = null,
        private readonly ?ExpenseGroupModel $expenseGroupModel = null,
        private readonly ?ExpenseGroupMemberModel $expenseGroupMemberModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return ($this->householdMembershipModel ?? new HouseholdMembershipModel())->findActiveMembershipsForUser($userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $userId, array $payload): array
    {
        $household = ($this->householdProvisioningService ?? service('householdProvisioning'))->create($userId, $payload);
        $this->setCurrent($userId, (string) $household['slug']);

        return $household;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveMembership(int $userId, string $identifier): ?array
    {
        return ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function householdSettings(int $userId, string $identifier): ?array
    {
        $membership = $this->resolveMembership($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);
        $settings = ($this->householdSettingModel ?? new HouseholdSettingModel())->findByHouseholdId((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'household' => $household,
            'settings' => $settings,
            'expenseGroups' => $this->expenseGroupsForHousehold((int) $membership['household_id']),
            'availableMembers' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment((int) $membership['household_id']),
            'categories' => ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->listAvailableForHousehold((int) $membership['household_id']),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function updateSettings(int $actorUserId, string $identifier, array $payload): ?array
    {
        if (! ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_SETTINGS)) {
            return null;
        }

        $context = $this->householdSettings($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $db = $this->db ?? Database::connect();
        $householdModel = $this->householdModel ?? new HouseholdModel($db);
        $householdSettingModel = $this->householdSettingModel ?? new HouseholdSettingModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $before = $this->settingsSnapshot($context['household'], $context['settings']);

        $db->transException(true)->transStart();

        $householdModel->update((int) $context['household']['id'], [
            'name' => (string) $payload['name'],
            'description' => $this->nullableString($payload['description'] ?? null),
            'base_currency' => strtoupper((string) $payload['base_currency']),
            'timezone' => (string) $payload['timezone'],
            'simplify_debts' => $this->boolish($payload['simplify_debts'] ?? false) ? 1 : 0,
            'chore_scoring_enabled' => $this->boolish($payload['chore_scoring_enabled'] ?? false) ? 1 : 0,
            'avatar_path' => $this->nullableString($payload['avatar_path'] ?? null),
        ]);

        if ($context['settings'] !== null) {
            $householdSettingModel->update((int) $context['settings']['id'], [
                'locale' => (string) ($payload['locale'] ?? $context['settings']['locale']),
            ]);
        }

        $auditLogService->record(
            action: 'household.settings_updated',
            entityType: 'household',
            entityId: (int) $context['household']['id'],
            actorUserId: $actorUserId,
            householdId: (int) $context['household']['id'],
            before: $before,
            after: $this->settingsSnapshot(
                $householdModel->find((int) $context['household']['id']),
                $householdSettingModel->findByHouseholdId((int) $context['household']['id']),
            ),
        );

        $db->transComplete();

        return $this->householdSettings($actorUserId, (string) $context['household']['slug']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function setCurrent(int $userId, string $identifier): ?array
    {
        $membership = $this->resolveMembership($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        ($this->householdContextService ?? service('householdContext'))->setActiveHousehold((string) $membership['household_slug']);

        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($this->db ?? Database::connect());
        $preferences = $userPreferenceModel->findByUserId($userId);

        if ($preferences === null) {
            $userPreferenceModel->insert([
                'user_id' => $userId,
                'default_household_id' => (int) $membership['household_id'],
                'notification_preferences_json' => null,
                'dashboard_preferences_json' => null,
            ]);
        } else {
            $userPreferenceModel->update((int) $preferences['id'], [
                'default_household_id' => (int) $membership['household_id'],
            ]);
        }

        return $membership;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createExpenseGroup(int $actorUserId, string $identifier, array $payload): ?array
    {
        helper('ui');

        if (! ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_SETTINGS)) {
            return null;
        }

        $context = $this->householdSettings($actorUserId, $identifier);
        if ($context === null) {
            return null;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $memberUserIds = $this->normalizeExpenseGroupMemberIds((int) $context['household']['id'], $payload['member_user_ids'] ?? []);

        if ($name === '') {
            throw new \DomainException(ui_text('settings.expense_groups.name_required'));
        }

        if ($memberUserIds === []) {
            throw new \DomainException(ui_text('settings.expense_groups.members_required'));
        }

        $db = $this->db ?? Database::connect();
        $model = $this->expenseGroupModel ?? new ExpenseGroupModel($db);
        $memberModel = $this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel($db);

        $db->transException(true)->transStart();

        $groupId = $model->insert([
            'household_id' => (int) $context['household']['id'],
            'name' => $name,
            'description' => $this->nullableString($payload['description'] ?? null),
            'color' => $this->nullableString($payload['color'] ?? null),
            'is_active' => 1,
            'created_by' => $actorUserId,
            'updated_by' => $actorUserId,
        ], true);

        $memberModel->replaceForGroup((int) $groupId, $memberUserIds);

        $db->transComplete();

        return $this->householdSettings($actorUserId, (string) $context['household']['slug']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateExpenseGroup(int $actorUserId, string $identifier, int $groupId, array $payload): ?array
    {
        helper('ui');

        if (! ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_SETTINGS)) {
            return null;
        }

        $context = $this->householdSettings($actorUserId, $identifier);
        if ($context === null) {
            return null;
        }

        $db = $this->db ?? Database::connect();
        $model = $this->expenseGroupModel ?? new ExpenseGroupModel($db);
        $memberModel = $this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel($db);
        $group = $model->findForHousehold((int) $context['household']['id'], $groupId);
        if ($group === null) {
            throw new \DomainException(ui_text('settings.expense_groups.not_found'));
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $memberUserIds = $this->normalizeExpenseGroupMemberIds((int) $context['household']['id'], $payload['member_user_ids'] ?? []);

        if ($name === '') {
            throw new \DomainException(ui_text('settings.expense_groups.name_required'));
        }

        if ($memberUserIds === []) {
            throw new \DomainException(ui_text('settings.expense_groups.members_required'));
        }

        $db->transException(true)->transStart();

        $model->update($groupId, [
            'name' => $name,
            'description' => $this->nullableString($payload['description'] ?? null),
            'color' => $this->nullableString($payload['color'] ?? null),
            'updated_by' => $actorUserId,
        ]);
        $memberModel->replaceForGroup($groupId, $memberUserIds);

        $db->transComplete();

        return $this->householdSettings($actorUserId, (string) $context['household']['slug']);
    }

    public function deleteExpenseGroup(int $actorUserId, string $identifier, int $groupId): ?array
    {
        helper('ui');

        if (! ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_SETTINGS)) {
            return null;
        }

        $context = $this->householdSettings($actorUserId, $identifier);
        if ($context === null) {
            return null;
        }

        $db = $this->db ?? Database::connect();
        $model = $this->expenseGroupModel ?? new ExpenseGroupModel($db);
        $group = $model->findForHousehold((int) $context['household']['id'], $groupId);
        if ($group === null) {
            throw new \DomainException(ui_text('settings.expense_groups.not_found'));
        }

        $model->delete($groupId);

        return $this->householdSettings($actorUserId, (string) $context['household']['slug']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenseGroupsForHousehold(int $householdId): array
    {
        $groups = ($this->expenseGroupModel ?? new ExpenseGroupModel($this->db ?? Database::connect()))
            ->listForHousehold($householdId);

        if ($groups === []) {
            return [];
        }

        $memberIdsByGroup = ($this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel($this->db ?? Database::connect()))
            ->userIdsByGroupIds(array_map(static fn (array $group): int => (int) $group['id'], $groups));
        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment($householdId);
        $memberDirectory = [];

        foreach ($members as $member) {
            $memberDirectory[(int) $member['user_id']] = $member;
        }

        foreach ($groups as &$group) {
            $userIds = $memberIdsByGroup[(int) $group['id']] ?? [];
            $group['member_user_ids'] = $userIds;
            $group['members'] = array_values(array_filter(
                array_map(static fn (int $userId): ?array => $memberDirectory[$userId] ?? null, $userIds)
            ));
        }

        unset($group);

        return $groups;
    }

    /**
     * @param mixed $rawValue
     * @return list<int>
     */
    private function normalizeExpenseGroupMemberIds(int $householdId, mixed $rawValue): array
    {
        $availableMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment($householdId);
        $allowedUserIds = array_map(static fn (array $member): int => (int) $member['user_id'], $availableMembers);
        $rawIds = is_array($rawValue) ? $rawValue : [$rawValue];
        $resolved = [];

        foreach ($rawIds as $value) {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                continue;
            }

            $userId = (int) $value;

            if (in_array($userId, $allowedUserIds, true) && ! in_array($userId, $resolved, true)) {
                $resolved[] = $userId;
            }
        }

        sort($resolved);

        return $resolved;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function boolish(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return ! empty($value);
    }

    /**
     * @param array<string, mixed>|null $household
     * @param array<string, mixed>|null $settings
     * @return array<string, mixed>
     */
    private function settingsSnapshot(?array $household, ?array $settings): array
    {
        return [
            'household' => $household ?? [],
            'settings' => $settings ?? [],
        ];
    }
}
