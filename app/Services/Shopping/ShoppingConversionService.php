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
use App\Services\Expenses\ExpenseService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DomainException;

final class ShoppingConversionService
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
        private readonly ?ExpenseService $expenseService = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function convertPurchasedItemsToExpense(int $userId, string $identifier, int $listId, array $payload): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($userId, $identifier);

        if (
            $membership === null
            || ! $authorization->hasPermission($userId, $identifier, Permission::MANAGE_SHOPPING)
            || ! $authorization->hasPermission($userId, $identifier, Permission::CREATE_EXPENSE)
        ) {
            throw new DomainException('Non hai i permessi necessari per convertire questi item in una spesa.');
        }

        $householdId = (int) $membership['household_id'];
        $household = ($this->householdModel ?? new HouseholdModel())->find($householdId);
        $list = ($this->shoppingListModel ?? new ShoppingListModel())->findDetailForHousehold($householdId, $listId);

        if ($household === null || $list === null) {
            throw new DomainException('Shopping list non trovata.');
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment($householdId);
        $memberIds = array_map(static fn (array $member): int => (int) $member['user_id'], $members);
        $itemIds = array_values(array_unique(array_filter(array_map('intval', (array) ($payload['item_ids'] ?? [])))));

        if ($itemIds === []) {
            throw new DomainException('Seleziona almeno un item acquistato da convertire.');
        }

        $items = ($this->shoppingItemModel ?? new ShoppingItemModel())->listPurchasedAvailableForConversion($householdId, $listId, $itemIds);

        if ($items === [] || count($items) !== count($itemIds)) {
            throw new DomainException('Alcuni item selezionati non sono acquistati o sono gia collegati a una spesa.');
        }

        $totalAmount = str_replace(',', '.', trim((string) ($payload['total_amount'] ?? '0')));
        $payerUserId = (int) ($payload['payer_user_id'] ?? 0);
        $participantIds = array_values(array_unique(array_filter(array_map('intval', (array) ($payload['participant_user_ids'] ?? [])))));

        if (! is_numeric($totalAmount) || (float) $totalAmount <= 0) {
            throw new DomainException('Specifica un totale valido per la spesa da generare.');
        }

        if (! in_array($payerUserId, $memberIds, true)) {
            throw new DomainException('Il pagatore selezionato non e un membro attivo della household.');
        }

        if ($participantIds === []) {
            throw new DomainException('Seleziona almeno un partecipante per la spesa.');
        }

        foreach ($participantIds as $participantId) {
            if (! in_array($participantId, $memberIds, true)) {
                throw new DomainException('Uno dei partecipanti selezionati non e attivo nella household.');
            }
        }

        $categoryId = $payload['category_id'] ?? null;

        if ($categoryId !== null && (string) $categoryId !== '') {
            $category = ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->findAvailableForHousehold($householdId, (int) $categoryId);

            if ($category === null) {
                throw new DomainException('La categoria spesa selezionata non e disponibile.');
            }
        }

        $normalizedTotal = number_format((float) $totalAmount, 2, '.', '');
        $expensePayload = [
            'title' => trim((string) ($payload['title'] ?? 'Shopping ' . (string) $list['name'])),
            'description' => $this->buildExpenseDescription($list, $items),
            'expense_date' => (string) ($payload['expense_date'] ?? date('Y-m-d')),
            'currency' => (string) ($household['base_currency'] ?? 'EUR'),
            'total_amount' => $normalizedTotal,
            'category_id' => $categoryId,
            'split_method' => 'equal',
            'payers' => [
                $payerUserId => [
                    'enabled' => 1,
                    'amount' => $normalizedTotal,
                ],
            ],
            'splits' => [],
        ];

        foreach ($participantIds as $participantId) {
            $expensePayload['splits'][$participantId] = ['enabled' => 1];
        }

        $db = $this->db ?? Database::connect();
        $audit = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $expenseService = $this->expenseService ?? new ExpenseService(db: $db);
        $itemModel = $this->shoppingItemModel ?? new ShoppingItemModel($db);

        $db->transException(true)->transStart();
        $expense = $expenseService->create($userId, $identifier, $expensePayload);

        foreach ($items as $item) {
            $itemModel->update((int) $item['id'], [
                'converted_expense_id' => (int) $expense['id'],
            ]);
        }

        $audit->record(
            action: 'shopping_list.converted_to_expense',
            entityType: 'shopping_list',
            entityId: $listId,
            actorUserId: $userId,
            householdId: $householdId,
            metadata: [
                'expense_id' => (int) $expense['id'],
                'item_ids' => array_map(static fn (array $item): int => (int) $item['id'], $items),
                'count' => count($items),
                'total_amount' => $normalizedTotal,
            ],
        );
        $db->transComplete();

        return $expense;
    }

    /**
     * @param array<string, mixed> $list
     * @param list<array<string, mixed>> $items
     */
    private function buildExpenseDescription(array $list, array $items): string
    {
        $lines = [
            'Converted from shopping list: ' . (string) $list['name'],
            '',
        ];

        foreach ($items as $item) {
            $lines[] = '- ' . (string) $item['name'] . ' (' . (string) $item['quantity'] . (empty($item['unit']) ? '' : ' ' . (string) $item['unit']) . ')';
        }

        return implode("\n", $lines);
    }
}
