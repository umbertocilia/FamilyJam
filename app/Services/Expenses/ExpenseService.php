<?php

declare(strict_types=1);

namespace App\Services\Expenses;

use App\Authorization\Permission;
use App\Models\Attachments\AttachmentModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Finance\ExpenseGroupModel;
use App\Models\Finance\ExpenseGroupMemberModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Services\Attachments\AttachmentStorageService;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DomainException;

final class ExpenseService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?ExpenseValidationService $expenseValidationService = null,
        private readonly ?AttachmentStorageService $attachmentStorageService = null,
        private readonly ?ExpenseModel $expenseModel = null,
        private readonly ?ExpensePayerModel $expensePayerModel = null,
        private readonly ?ExpenseSplitModel $expenseSplitModel = null,
        private readonly ?ExpenseCategoryModel $expenseCategoryModel = null,
        private readonly ?ExpenseGroupModel $expenseGroupModel = null,
        private readonly ?ExpenseGroupMemberModel $expenseGroupMemberModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?AttachmentModel $attachmentModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?AuditLogModel $auditLogModel = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{membership: array<string, mixed>, expenses: list<array<string, mixed>>, categories: list<array<string, mixed>>, expenseGroups: list<array<string, mixed>>, members: list<array<string, mixed>>, availableMembers: list<array<string, mixed>>, filters: array<string, mixed>}|null
     */
    public function listContext(int $actorUserId, string $identifier, array $filters = []): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $householdId = (int) $membership['household_id'];
        $normalizedFilters = [
            'category_id' => $filters['category_id'] ?? null,
            'expense_group_id' => $filters['expense_group_id'] ?? null,
            'month' => $filters['month'] ?? null,
            'member_id' => $filters['member_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ];
        $availableMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment($householdId);

        return [
            'membership' => $membership,
            'expenses' => ($this->expenseModel ?? new ExpenseModel())->listForHousehold($householdId, $normalizedFilters),
            'categories' => ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->listAvailableForHousehold($householdId),
            'expenseGroups' => $this->listExpenseGroups($householdId, $availableMembers),
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold($householdId),
            'availableMembers' => $availableMembers,
            'filters' => $normalizedFilters,
        ];
    }

    /**
     * @return array{membership: array<string, mixed>, categories: list<array<string, mixed>>, expenseGroups: list<array<string, mixed>>, members: list<array<string, mixed>>, expense: array<string, mixed>|null, payers: list<array<string, mixed>>, splits: list<array<string, mixed>>}|null
     */
    public function formContext(int $actorUserId, string $identifier, ?int $expenseId = null): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $expense = null;
        $payers = [];
        $splits = [];

        if ($expenseId !== null) {
            $expense = $this->guardEditableExpense($actorUserId, $identifier, $expenseId);

            if ($expense === null) {
                return null;
            }

            $payers = ($this->expensePayerModel ?? new ExpensePayerModel())->listForExpense($expenseId);
            $splits = ($this->expenseSplitModel ?? new ExpenseSplitModel())->listForExpense($expenseId);
        }

        $householdId = (int) $membership['household_id'];
        $availableMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listActiveMembersForAssignment($householdId);

        return [
            'membership' => $membership,
            'categories' => ($this->expenseCategoryModel ?? new ExpenseCategoryModel())->listAvailableForHousehold($householdId),
            'expenseGroups' => $this->listExpenseGroups($householdId, $availableMembers),
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold($householdId),
            'expense' => $expense,
            'payers' => $payers,
            'splits' => $splits,
        ];
    }

    /**
     * @return array{membership: array<string, mixed>, expense: array<string, mixed>, payers: list<array<string, mixed>>, splits: list<array<string, mixed>>, audit_logs: list<array<string, mixed>>}|null
     */
    public function detailContext(int $actorUserId, string $identifier, int $expenseId): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $expense = ($this->expenseModel ?? new ExpenseModel())
            ->findDetailForHousehold((int) $membership['household_id'], $expenseId);

        if ($expense === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'expense' => $expense,
            'payers' => ($this->expensePayerModel ?? new ExpensePayerModel())->listForExpense($expenseId),
            'splits' => ($this->expenseSplitModel ?? new ExpenseSplitModel())->listForExpense($expenseId),
            'audit_logs' => ($this->auditLogModel ?? new AuditLogModel())->listForEntity('expense', $expenseId, (int) $membership['household_id']),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $actorUserId, string $identifier, array $payload, ?UploadedFile $receipt = null): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($actorUserId, $identifier, Permission::CREATE_EXPENSE)) {
            throw new DomainException('Non hai i permessi necessari per creare una spesa.');
        }

        $db = $this->db ?? Database::connect();
        $expenseModel = $this->expenseModel ?? new ExpenseModel($db);
        $expensePayerModel = $this->expensePayerModel ?? new ExpensePayerModel($db);
        $expenseSplitModel = $this->expenseSplitModel ?? new ExpenseSplitModel($db);
        $attachmentStorageService = $this->attachmentStorageService ?? new AttachmentStorageService();
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $notificationService = $this->notificationService ?? new NotificationService($db);
        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel($db))
            ->listForHousehold((int) $membership['household_id']);
        $activeMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel($db))
            ->listActiveMembersForAssignment((int) $membership['household_id']);
        $normalized = ($this->expenseValidationService ?? new ExpenseValidationService())
            ->validateAndNormalize((int) $membership['household_id'], $payload, $members);

        $db->transException(true)->transStart();

        $expenseId = $expenseModel->insert([
            'household_id' => (int) $membership['household_id'],
            'recurring_rule_id' => null,
            'category_id' => $this->nullableInt($payload['category_id'] ?? null),
            'expense_group_id' => $this->nullableInt($payload['expense_group_id'] ?? null),
            'receipt_attachment_id' => null,
            'title' => trim((string) $payload['title']),
            'description' => $this->nullableString($payload['description'] ?? null),
            'expense_date' => (string) $payload['expense_date'],
            'currency' => strtoupper((string) $payload['currency']),
            'total_amount' => $normalized['total_amount'],
            'split_method' => (string) $payload['split_method'],
            'status' => 'active',
            'created_by' => $actorUserId,
            'updated_by' => $actorUserId,
        ], true);

        $expensePayerModel->replaceForExpense((int) $expenseId, $normalized['payers']);
        $expenseSplitModel->replaceForExpense((int) $expenseId, $normalized['splits']);

        $attachment = $attachmentStorageService->storeExpenseReceipt($receipt, (int) $membership['household_id'], $actorUserId, (int) $expenseId);

        if ($attachment !== null) {
            $expenseModel->update((int) $expenseId, ['receipt_attachment_id' => (int) $attachment['id']]);
            $attachmentStorageService->bindToExpense((int) $attachment['id'], (int) $expenseId);
        }

        $after = $this->snapshot((int) $membership['household_id'], (int) $expenseId, $expenseModel, $expensePayerModel, $expenseSplitModel);
        $auditLogService->record(
            action: 'expense.created',
            entityType: 'expense',
            entityId: (int) $expenseId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            after: $after,
        );

        $notificationService->notifyExpenseCreated(
            array_values(array_unique(array_map(static fn (array $member): int => (int) $member['user_id'], $activeMembers))),
            (int) $membership['household_id'],
            $identifier,
            (int) $expenseId,
            trim((string) $payload['title']),
            $actorUserId,
        );

        $db->transComplete();

        return $expenseModel->findDetailForHousehold((int) $membership['household_id'], (int) $expenseId) ?? [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $actorUserId, string $identifier, int $expenseId, array $payload, ?UploadedFile $receipt = null): array
    {
        $expense = $this->guardEditableExpense($actorUserId, $identifier, $expenseId);

        if ($expense === null) {
            throw new DomainException('Spesa non disponibile o non modificabile.');
        }

        $db = $this->db ?? Database::connect();
        $expenseModel = $this->expenseModel ?? new ExpenseModel($db);
        $expensePayerModel = $this->expensePayerModel ?? new ExpensePayerModel($db);
        $expenseSplitModel = $this->expenseSplitModel ?? new ExpenseSplitModel($db);
        $attachmentStorageService = $this->attachmentStorageService ?? new AttachmentStorageService();
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $notificationService = $this->notificationService ?? new NotificationService($db);
        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel($db))
            ->listForHousehold((int) $expense['household_id']);
        $activeMembers = ($this->householdMembershipModel ?? new HouseholdMembershipModel($db))
            ->listActiveMembersForAssignment((int) $expense['household_id']);
        $normalized = ($this->expenseValidationService ?? new ExpenseValidationService())
            ->validateAndNormalize((int) $expense['household_id'], $payload, $members);
        $before = $this->snapshot((int) $expense['household_id'], $expenseId, $expenseModel, $expensePayerModel, $expenseSplitModel);

        $db->transException(true)->transStart();

        $expenseModel->update($expenseId, [
            'category_id' => $this->nullableInt($payload['category_id'] ?? null),
            'expense_group_id' => $this->nullableInt($payload['expense_group_id'] ?? null),
            'title' => trim((string) $payload['title']),
            'description' => $this->nullableString($payload['description'] ?? null),
            'expense_date' => (string) $payload['expense_date'],
            'currency' => strtoupper((string) $payload['currency']),
            'total_amount' => $normalized['total_amount'],
            'split_method' => (string) $payload['split_method'],
            'status' => 'edited',
            'updated_by' => $actorUserId,
        ]);

        $expensePayerModel->replaceForExpense($expenseId, $normalized['payers']);
        $expenseSplitModel->replaceForExpense($expenseId, $normalized['splits']);

        $attachment = $attachmentStorageService->storeExpenseReceipt($receipt, (int) $expense['household_id'], $actorUserId, $expenseId);

        if ($attachment !== null) {
            if (! empty($expense['receipt_attachment_id'])) {
                $attachmentStorageService->softDelete((int) $expense['receipt_attachment_id']);
            }

            $expenseModel->update($expenseId, ['receipt_attachment_id' => (int) $attachment['id']]);
            $attachmentStorageService->bindToExpense((int) $attachment['id'], $expenseId);
        }

        $after = $this->snapshot((int) $expense['household_id'], $expenseId, $expenseModel, $expensePayerModel, $expenseSplitModel);
        $auditLogService->record(
            action: 'expense.updated',
            entityType: 'expense',
            entityId: $expenseId,
            actorUserId: $actorUserId,
            householdId: (int) $expense['household_id'],
            before: $before,
            after: $after,
        );

        $notificationService->notifyExpenseUpdated(
            array_values(array_unique(array_map(static fn (array $member): int => (int) $member['user_id'], $activeMembers))),
            (int) $expense['household_id'],
            $identifier,
            $expenseId,
            trim((string) $payload['title']),
            $actorUserId,
        );

        $db->transComplete();

        return $expenseModel->findDetailForHousehold((int) $expense['household_id'], $expenseId) ?? [];
    }

    public function softDelete(int $actorUserId, string $identifier, int $expenseId): void
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($actorUserId, $identifier, Permission::DELETE_EXPENSE)) {
            throw new DomainException('Non hai i permessi necessari per eliminare questa spesa.');
        }

        $db = $this->db ?? Database::connect();
        $expenseModel = $this->expenseModel ?? new ExpenseModel($db);
        $expensePayerModel = $this->expensePayerModel ?? new ExpensePayerModel($db);
        $expenseSplitModel = $this->expenseSplitModel ?? new ExpenseSplitModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $expense = $expenseModel->findDetailForHousehold((int) $membership['household_id'], $expenseId, true);

        if ($expense === null || $expense['deleted_at'] !== null) {
            throw new DomainException('Spesa non disponibile per l\'eliminazione.');
        }

        $before = $this->snapshot((int) $membership['household_id'], $expenseId, $expenseModel, $expensePayerModel, $expenseSplitModel);

        $db->transException(true)->transStart();

        $expenseModel->update($expenseId, [
            'status' => 'deleted',
            'updated_by' => $actorUserId,
        ]);
        $expenseModel->delete($expenseId);

        $after = $this->snapshot((int) $membership['household_id'], $expenseId, $expenseModel, $expensePayerModel, $expenseSplitModel);
        $auditLogService->record(
            action: 'expense.deleted',
            entityType: 'expense',
            entityId: $expenseId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            before: $before,
            after: $after,
        );

        $db->transComplete();
    }

    /**
     * @return array{expense: array<string, mixed>, attachment: array<string, mixed>}|null
     */
    public function receiptContext(int $actorUserId, string $identifier, int $expenseId): ?array
    {
        $detail = $this->detailContext($actorUserId, $identifier, $expenseId);

        if ($detail === null || empty($detail['expense']['receipt_attachment_id'])) {
            return null;
        }

        $attachment = ($this->attachmentModel ?? new AttachmentModel())
            ->findForHousehold((int) $detail['membership']['household_id'], (int) $detail['expense']['receipt_attachment_id']);

        if ($attachment === null) {
            return null;
        }

        $absolutePath = ($this->attachmentStorageService ?? new AttachmentStorageService())->absolutePath($attachment);

        if (! is_file($absolutePath)) {
            return null;
        }

        return [
            'expense' => $detail['expense'],
            'attachment' => $attachment,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function guardEditableExpense(int $actorUserId, string $identifier, int $expenseId): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $expense = ($this->expenseModel ?? new ExpenseModel())
            ->findDetailForHousehold((int) $membership['household_id'], $expenseId);

        if ($expense === null || $expense['deleted_at'] !== null) {
            return null;
        }

        if (! $authorization->canManage($actorUserId, $identifier, 'edit_expense', $expense)) {
            return null;
        }

        return $expense;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(
        int $householdId,
        int $expenseId,
        ExpenseModel $expenseModel,
        ExpensePayerModel $expensePayerModel,
        ExpenseSplitModel $expenseSplitModel,
    ): array {
        return [
            'expense' => $expenseModel->findDetailForHousehold($householdId, $expenseId, true),
            'payers' => $expensePayerModel->listForExpense($expenseId),
            'splits' => $expenseSplitModel->listForExpense($expenseId),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param list<array<string, mixed>> $availableMembers
     * @return list<array<string, mixed>>
     */
    private function listExpenseGroups(int $householdId, array $availableMembers): array
    {
        $groups = ($this->expenseGroupModel ?? new ExpenseGroupModel())->listForHousehold($householdId);

        if ($groups === []) {
            return [];
        }

        $memberIdsByGroup = ($this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel())
            ->userIdsByGroupIds(array_map(static fn (array $group): int => (int) $group['id'], $groups));
        $memberDirectory = [];

        foreach ($availableMembers as $member) {
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
}
