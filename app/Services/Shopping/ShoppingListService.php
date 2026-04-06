<?php

declare(strict_types=1);

namespace App\Services\Shopping;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DomainException;

final class ShoppingListService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ShoppingListModel $shoppingListModel = null,
        private readonly ?ShoppingItemModel $shoppingItemModel = null,
        private readonly ?ExpenseCategoryModel $expenseCategoryModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function indexContext(int $userId, string $identifier): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $lists = ($this->shoppingListModel ?? new ShoppingListModel())->listForHousehold((int) $context['household']['id']);

        return array_merge($context, [
            'lists' => $lists,
            'summary' => [
                'lists' => count($lists),
                'open_items' => array_sum(array_map(static fn (array $row): int => (int) ($row['open_count'] ?? 0), $lists)),
                'purchased_items' => array_sum(array_map(static fn (array $row): int => (int) ($row['purchased_count'] ?? 0), $lists)),
                'urgent_items' => array_sum(array_map(static fn (array $row): int => (int) ($row['urgent_open_count'] ?? 0), $lists)),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailContext(int $userId, string $identifier, int $listId): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $list = ($this->shoppingListModel ?? new ShoppingListModel())
            ->findDetailForHousehold((int) $context['household']['id'], $listId);

        if ($list === null) {
            return null;
        }

        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel();
        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment((int) $context['household']['id']);

        return array_merge($context, [
            'list' => $list,
            'openItems' => $itemModel->listForList((int) $context['household']['id'], $listId, false),
            'purchasedItems' => $itemModel->listForList((int) $context['household']['id'], $listId, true),
            'members' => $members,
            'categories' => ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->listAvailableForHousehold((int) $context['household']['id']),
            'conversionDefaults' => [
                'title' => 'Shopping ' . (string) $list['name'],
                'expense_date' => date('Y-m-d'),
                'payer_user_id' => $userId,
                'participant_user_ids' => array_map(static fn (array $member): int => (int) $member['user_id'], $members),
                'currency' => (string) ($context['household']['base_currency'] ?? 'EUR'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formContext(int $userId, string $identifier, ?int $listId = null): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManageShopping']) {
            return null;
        }

        $list = null;

        if ($listId !== null) {
            $list = ($this->shoppingListModel ?? new ShoppingListModel())
                ->findDetailForHousehold((int) $context['household']['id'], $listId);

            if ($list === null) {
                return null;
            }
        }

        return array_merge($context, ['list' => $list]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $userId, string $identifier, array $payload): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManageShopping']) {
            throw new DomainException('Non hai i permessi necessari per creare una shopping list.');
        }

        $name = $this->normalizeListName($payload['name'] ?? null);
        $isDefault = $this->isTruthy($payload['is_default'] ?? null) ? 1 : 0;
        $db = $this->db ?? Database::connect();
        $listModel = $this->shoppingListModel ?? new ShoppingListModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $activeLists = $listModel->listForHousehold((int) $context['household']['id']);

        $db->transException(true)->transStart();

        if ($isDefault === 1 || $activeLists === []) {
            $listModel->clearDefaultForHousehold((int) $context['household']['id']);
            $isDefault = 1;
        }

        $listId = $listModel->insert([
            'household_id' => (int) $context['household']['id'],
            'name' => $name,
            'is_default' => $isDefault,
            'created_by' => $userId,
        ], true);

        $after = $listModel->findDetailForHousehold((int) $context['household']['id'], (int) $listId) ?? [];
        $audit->record(
            action: 'shopping_list.created',
            entityType: 'shopping_list',
            entityId: (int) $listId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            after: $after,
        );

        $db->transComplete();

        return $after;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $userId, string $identifier, int $listId, array $payload): array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManageShopping']) {
            throw new DomainException('Non hai i permessi necessari per aggiornare questa shopping list.');
        }

        $db = $this->db ?? Database::connect();
        $listModel = $this->shoppingListModel ?? new ShoppingListModel($db);
        $existing = $listModel->findDetailForHousehold((int) $context['household']['id'], $listId);

        if ($existing === null) {
            throw new DomainException('Shopping list non trovata.');
        }

        $name = $this->normalizeListName($payload['name'] ?? null);
        $isDefault = $this->isTruthy($payload['is_default'] ?? null) ? 1 : 0;
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();

        if ($isDefault === 1) {
            $listModel->clearDefaultForHousehold((int) $context['household']['id'], $listId);
        } elseif ((int) ($existing['is_default'] ?? 0) === 1) {
            $isDefault = 1;
        }

        $listModel->update($listId, [
            'name' => $name,
            'is_default' => $isDefault,
        ]);

        $after = $listModel->findDetailForHousehold((int) $context['household']['id'], $listId) ?? [];
        $audit->record(
            action: 'shopping_list.updated',
            entityType: 'shopping_list',
            entityId: $listId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );

        $db->transComplete();

        return $after;
    }

    public function softDelete(int $userId, string $identifier, int $listId): void
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null || ! $context['canManageShopping']) {
            throw new DomainException('Non hai i permessi necessari per eliminare questa shopping list.');
        }

        $db = $this->db ?? Database::connect();
        $listModel = $this->shoppingListModel ?? new ShoppingListModel($db);
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);
        $existing = $listModel->findDetailForHousehold((int) $context['household']['id'], $listId);

        if ($existing === null) {
            throw new DomainException('Shopping list non trovata.');
        }

        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        foreach (array_merge(
            $itemModel->listForList((int) $context['household']['id'], $listId, false),
            $itemModel->listForList((int) $context['household']['id'], $listId, true),
        ) as $item) {
            $itemModel->delete((int) $item['id']);
        }

        $listModel->delete($listId);

        if ((int) ($existing['is_default'] ?? 0) === 1) {
            $replacement = null;
            foreach ($listModel->listForHousehold((int) $context['household']['id']) as $candidate) {
                if ((int) $candidate['id'] !== $listId) {
                    $replacement = $candidate;
                    break;
                }
            }

            if ($replacement !== null) {
                $listModel->update((int) $replacement['id'], ['is_default' => 1]);
            }
        }

        $after = $listModel->findDetailForHousehold((int) $context['household']['id'], $listId, true) ?? [];
        $audit->record(
            action: 'shopping_list.deleted',
            entityType: 'shopping_list',
            entityId: $listId,
            actorUserId: $userId,
            householdId: (int) $context['household']['id'],
            before: $existing,
            after: $after,
        );
        $db->transComplete();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $userId, string $identifier): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');

        return [
            'membership' => $membership,
            'household' => $household,
            'canManageShopping' => $authorization->hasPermission($userId, $identifier, Permission::MANAGE_SHOPPING),
            'canCreateExpense' => $authorization->hasPermission($userId, $identifier, Permission::CREATE_EXPENSE),
        ];
    }

    private function normalizeListName(mixed $value): string
    {
        $name = trim((string) $value);

        if ($name === '' || strlen($name) < 2 || strlen($name) > 120) {
            throw new DomainException('Il nome lista deve avere tra 2 e 120 caratteri.');
        }

        return $name;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return is_string($value) && in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }
}
