<?php

declare(strict_types=1);

namespace App\Services\Shopping;

use App\Authorization\Permission;
use App\Models\Audit\AuditLogModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Models\Shopping\ShoppingListModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use DomainException;

final class ShoppingItemService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ShoppingListModel $shoppingListModel = null,
        private readonly ?ShoppingItemModel $shoppingItemModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function quickAdd(int $userId, string $identifier, int $listId, array $payload): array
    {
        [$context, $list, $members] = $this->resolveWritableList($userId, $identifier, $listId);
        $normalized = $this->normalizePayload($payload, $members, true);
        $db = $this->db ?? Database::connect();
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $itemId = $itemModel->insert([
            'shopping_list_id' => $listId,
            'household_id' => (int) $context['membership']['household_id'],
            'name' => $normalized['name'],
            'quantity' => $normalized['quantity'],
            'unit' => $normalized['unit'],
            'category' => $normalized['category'],
            'notes' => $normalized['notes'],
            'priority' => $normalized['priority'],
            'assigned_user_id' => $normalized['assigned_user_id'],
            'position' => $itemModel->nextPosition($listId),
            'is_purchased' => 0,
            'purchased_at' => null,
            'purchased_by' => null,
            'converted_expense_id' => null,
            'created_by' => $userId,
        ], true);
        $after = $itemModel->findDetailForHousehold((int) $context['membership']['household_id'], (int) $itemId) ?? [];
        $audit->record(
            action: 'shopping_item.created',
            entityType: 'shopping_item',
            entityId: (int) $itemId,
            actorUserId: $userId,
            householdId: (int) $context['membership']['household_id'],
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $userId, string $identifier, int $itemId, array $payload): array
    {
        [$context, $item, $members] = $this->resolveWritableItem($userId, $identifier, $itemId);
        $normalized = $this->normalizePayload($payload, $members, false);
        $db = $this->db ?? Database::connect();
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $itemModel->update($itemId, [
            'name' => $normalized['name'],
            'quantity' => $normalized['quantity'],
            'unit' => $normalized['unit'],
            'category' => $normalized['category'],
            'notes' => $normalized['notes'],
            'priority' => $normalized['priority'],
            'assigned_user_id' => $normalized['assigned_user_id'],
            'position' => $normalized['position'] ?? (int) ($item['position'] ?? 0),
        ]);
        $after = $itemModel->findDetailForHousehold((int) $context['membership']['household_id'], $itemId) ?? [];
        $audit->record(
            action: 'shopping_item.updated',
            entityType: 'shopping_item',
            entityId: $itemId,
            actorUserId: $userId,
            householdId: (int) $context['membership']['household_id'],
            before: $item,
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    /**
     * @return array<string, mixed>
     */
    public function togglePurchased(int $userId, string $identifier, int $itemId): array
    {
        [$context, $item] = $this->resolveWritableItem($userId, $identifier, $itemId);

        if ((int) ($item['is_purchased'] ?? 0) === 1 && ! empty($item['converted_expense_id'])) {
            throw new DomainException('Questo item e gia stato convertito in una spesa e non puo tornare aperto.');
        }

        $db = $this->db ?? Database::connect();
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $isPurchased = (int) ($item['is_purchased'] ?? 0) === 1 ? 0 : 1;
        $now = new DateTimeImmutable('now');

        $db->transException(true)->transStart();
        $itemModel->update($itemId, [
            'is_purchased' => $isPurchased,
            'purchased_at' => $isPurchased === 1 ? $now->format('Y-m-d H:i:s') : null,
            'purchased_by' => $isPurchased === 1 ? $userId : null,
        ]);
        $after = $itemModel->findDetailForHousehold((int) $context['membership']['household_id'], $itemId) ?? [];
        $audit->record(
            action: $isPurchased === 1 ? 'shopping_item.purchased' : 'shopping_item.unpurchased',
            entityType: 'shopping_item',
            entityId: $itemId,
            actorUserId: $userId,
            householdId: (int) $context['membership']['household_id'],
            before: $item,
            after: $after,
        );
        $db->transComplete();

        return $after;
    }

    public function softDelete(int $userId, string $identifier, int $itemId): void
    {
        [$context, $item] = $this->resolveWritableItem($userId, $identifier, $itemId);
        $db = $this->db ?? Database::connect();
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();
        $itemModel->delete($itemId);
        $after = $itemModel->findDetailForHousehold((int) $context['membership']['household_id'], $itemId, true) ?? [];
        $audit->record(
            action: 'shopping_item.deleted',
            entityType: 'shopping_item',
            entityId: $itemId,
            actorUserId: $userId,
            householdId: (int) $context['membership']['household_id'],
            before: $item,
            after: $after,
        );
        $db->transComplete();
    }

    /**
     * @param list<int> $itemIds
     * @return list<array<string, mixed>>
     */
    public function bulkPurchase(int $userId, string $identifier, int $listId, array $itemIds, bool $markPurchased): array
    {
        [$context] = $this->resolveWritableList($userId, $identifier, $listId);

        if ($itemIds === []) {
            throw new DomainException('Seleziona almeno un item.');
        }

        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel();
        $selected = [];

        foreach (array_values(array_unique($itemIds)) as $itemId) {
            $item = $itemModel->findDetailForHousehold((int) $context['membership']['household_id'], $itemId);

            if ($item === null || (int) $item['shopping_list_id'] !== $listId) {
                throw new DomainException('Uno degli item selezionati non appartiene alla lista.');
            }

            if (! $markPurchased && ! empty($item['converted_expense_id'])) {
                throw new DomainException('Gli item gia convertiti in spesa non possono tornare aperti.');
            }

            $selected[] = $item;
        }

        $db = $this->db ?? Database::connect();
        $now = new DateTimeImmutable('now');
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);

        $db->transException(true)->transStart();
        foreach ($selected as $item) {
            $itemModel->update((int) $item['id'], [
                'is_purchased' => $markPurchased ? 1 : 0,
                'purchased_at' => $markPurchased ? $now->format('Y-m-d H:i:s') : null,
                'purchased_by' => $markPurchased ? $userId : null,
            ]);
        }

        $audit->record(
            action: $markPurchased ? 'shopping_list.bulk_purchased' : 'shopping_list.bulk_unpurchased',
            entityType: 'shopping_list',
            entityId: $listId,
            actorUserId: $userId,
            householdId: (int) $context['membership']['household_id'],
            metadata: [
                'item_ids' => array_map(static fn (array $item): int => (int) $item['id'], $selected),
                'count' => count($selected),
            ],
        );
        $db->transComplete();

        return $itemModel->listForList((int) $context['membership']['household_id'], $listId, $markPurchased);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: list<array<string, mixed>>}
     */
    private function resolveWritableList(int $userId, string $identifier, int $listId): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($userId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($userId, $identifier, Permission::MANAGE_SHOPPING)) {
            throw new DomainException('Non hai i permessi necessari per modificare questa lista.');
        }

        $list = ($this->shoppingListModel ?? new ShoppingListModel())
            ->findDetailForHousehold((int) $membership['household_id'], $listId);

        if ($list === null) {
            throw new DomainException('Shopping list non trovata.');
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment((int) $membership['household_id']);

        return [['membership' => $membership], $list, $members];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: list<array<string, mixed>>}
     */
    private function resolveWritableItem(int $userId, string $identifier, int $itemId): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($userId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($userId, $identifier, Permission::MANAGE_SHOPPING)) {
            throw new DomainException('Non hai i permessi necessari per modificare questo item.');
        }

        $item = ($this->shoppingItemModel ?? new ShoppingItemModel())
            ->findDetailForHousehold((int) $membership['household_id'], $itemId);

        if ($item === null) {
            throw new DomainException('Shopping item non trovato.');
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listActiveMembersForAssignment((int) $membership['household_id']);

        return [['membership' => $membership], $item, $members];
    }

    /**
     * @param list<array<string, mixed>> $members
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload, array $members, bool $isCreate): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $quantityRaw = str_replace(',', '.', trim((string) ($payload['quantity'] ?? '1')));
        $priority = strtolower(trim((string) ($payload['priority'] ?? 'normal')));
        $assignedUserId = ($payload['assigned_user_id'] ?? null) === null || (string) ($payload['assigned_user_id'] ?? '') === '' ? null : (int) $payload['assigned_user_id'];
        $memberIds = array_map(static fn (array $member): int => (int) $member['user_id'], $members);

        if ($name === '' || strlen($name) > 160) {
            throw new DomainException('Il nome item e obbligatorio e non puo superare 160 caratteri.');
        }

        if ($quantityRaw === '' || ! is_numeric($quantityRaw) || (float) $quantityRaw <= 0) {
            throw new DomainException('La quantita deve essere numerica e maggiore di zero.');
        }

        if (! in_array($priority, ['urgent', 'high', 'normal', 'low'], true)) {
            throw new DomainException('La priorita selezionata non e valida.');
        }

        if ($assignedUserId !== null && ! in_array($assignedUserId, $memberIds, true)) {
            throw new DomainException('L assegnatario selezionato non e attivo nella household.');
        }

        $normalized = [
            'name' => $name,
            'quantity' => number_format((float) $quantityRaw, 2, '.', ''),
            'unit' => $this->nullableString($payload['unit'] ?? null, 32),
            'category' => $this->nullableString($payload['category'] ?? null, 64),
            'notes' => $this->nullableString($payload['notes'] ?? null, 4000),
            'priority' => $priority,
            'assigned_user_id' => $assignedUserId,
        ];

        if (! $isCreate && isset($payload['position']) && (string) $payload['position'] !== '') {
            $normalized['position'] = max(0, (int) $payload['position']);
        }

        return $normalized;
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        $resolved = trim((string) $value);

        if ($resolved === '') {
            return null;
        }

        return substr($resolved, 0, $maxLength);
    }
}
