<?php

declare(strict_types=1);

namespace App\Services\Balances;

use App\Authorization\Permission;
use App\Models\Attachments\AttachmentModel;
use App\Models\Audit\AuditLogModel;
use App\Models\Finance\ExpenseGroupMemberModel;
use App\Models\Finance\ExpenseGroupModel;
use App\Models\Finance\SettlementModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Attachments\AttachmentStorageService;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DomainException;

final class SettlementService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ExpenseGroupModel $expenseGroupModel = null,
        private readonly ?ExpenseGroupMemberModel $expenseGroupMemberModel = null,
        private readonly ?SettlementModel $settlementModel = null,
        private readonly ?AttachmentStorageService $attachmentStorageService = null,
        private readonly ?AttachmentModel $attachmentModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?BalanceService $balanceService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function listContext(int $actorUserId, string $identifier): ?array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        return array_merge($context, [
            'settlements' => ($this->settlementModel ?? new SettlementModel())->listForHousehold((int) $context['household']['id']),
            'expenseGroups' => ($this->expenseGroupModel ?? new ExpenseGroupModel())->listForHousehold((int) $context['household']['id']),
            'canCreateSettlement' => ($this->householdAuthorizationService ?? service('householdAuthorization'))
                ->hasPermission($actorUserId, (string) $context['household']['slug'], Permission::ADD_SETTLEMENT),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formContext(int $actorUserId, string $identifier): ?array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $balanceContext = ($this->balanceService ?? new BalanceService())->pairwiseContext($actorUserId, $identifier);

        return array_merge($context, [
            'pairwiseBalances' => $balanceContext['pairwiseBalances'] ?? [],
            'simplifiedTransfers' => $balanceContext['simplifiedTransfers'] ?? [],
            'expenseGroups' => ($this->expenseGroupModel ?? new ExpenseGroupModel())->listForHousehold((int) $context['household']['id']),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $actorUserId, string $identifier, array $payload, ?UploadedFile $attachment = null): array
    {
        helper('ui');
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null || ! $authorization->hasPermission($actorUserId, $identifier, Permission::ADD_SETTLEMENT)) {
            throw new DomainException(ui_locale() === 'it'
                ? 'Non hai i permessi necessari per registrare un rimborso.'
                : 'You do not have permission to record a settlement.');
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listForHousehold((int) $membership['household_id']);
        $memberIds = array_values(array_unique(array_map(static fn (array $member): int => (int) $member['user_id'], $members)));
        $fromUserId = (int) ($payload['from_user_id'] ?? 0);
        $toUserId = (int) ($payload['to_user_id'] ?? 0);
        $expenseGroupId = ($payload['expense_group_id'] ?? null) !== null && (string) ($payload['expense_group_id'] ?? '') !== '' ? (int) $payload['expense_group_id'] : null;
        $amount = $this->normalizeMoney((string) ($payload['amount'] ?? '0'));
        $db = $this->db ?? Database::connect();

        if ($expenseGroupId !== null) {
            $group = ($this->expenseGroupModel ?? new ExpenseGroupModel($db))->findForHousehold((int) $membership['household_id'], $expenseGroupId);

            if ($group === null) {
                throw new DomainException(ui_text('expense.error.group_invalid'));
            }

            $groupMemberIds = ($this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel($db))
                ->userIdsByGroupIds([$expenseGroupId])[$expenseGroupId] ?? [];
            $memberIds = array_values(array_unique($groupMemberIds));
        }

        if ($fromUserId <= 0 || $toUserId <= 0 || ! in_array($fromUserId, $memberIds, true) || ! in_array($toUserId, $memberIds, true)) {
            throw new DomainException(ui_locale() === 'it'
                ? 'I membri del rimborso devono appartenere alla household o al gruppo spesa selezionato.'
                : 'Settlement members must belong to the active household or selected expense group.');
        }

        if ($fromUserId === $toUserId) {
            throw new DomainException(ui_locale() === 'it'
                ? 'Un rimborso richiede due utenti distinti.'
                : 'A settlement requires two different users.');
        }

        if ($this->toCents($amount) <= 0) {
            throw new DomainException(ui_locale() === 'it'
                ? 'L\'importo del rimborso deve essere maggiore di zero.'
                : 'Settlement amount must be greater than zero.');
        }

        $settlementModel = $this->settlementModel ?? new SettlementModel($db);
        $attachmentStorage = $this->attachmentStorageService ?? new AttachmentStorageService();
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $notificationService = $this->notificationService ?? new NotificationService($db);

        $db->transException(true)->transStart();

        $settlementId = $settlementModel->insert([
            'household_id' => (int) $membership['household_id'],
            'expense_group_id' => $expenseGroupId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'attachment_id' => null,
            'settlement_date' => (string) $payload['settlement_date'],
            'currency' => strtoupper((string) $payload['currency']),
            'amount' => $amount,
            'payment_method' => $this->nullableString($payload['payment_method'] ?? null),
            'note' => $this->nullableString($payload['note'] ?? null),
            'created_by' => $actorUserId,
        ], true);

        $storedAttachment = $attachmentStorage->storeSettlementAttachment(
            $attachment,
            (int) $membership['household_id'],
            $actorUserId,
            (int) $settlementId,
        );

        if ($storedAttachment !== null) {
            $settlementModel->update((int) $settlementId, ['attachment_id' => (int) $storedAttachment['id']]);
            $attachmentStorage->bindToSettlement((int) $storedAttachment['id'], (int) $settlementId);
        }

        $settlement = $settlementModel->findDetailForHousehold((int) $membership['household_id'], (int) $settlementId);

        $auditLogService->record(
            action: 'settlement.created',
            entityType: 'settlement',
            entityId: (int) $settlementId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            after: $settlement,
        );

        $notificationService->notifySettlementCreated(
            [$fromUserId, $toUserId],
            (int) $membership['household_id'],
            $identifier,
            (int) $settlementId,
            sprintf('%s %s', strtoupper((string) $payload['currency']), $amount),
            $actorUserId,
        );

        $db->transComplete();

        return $settlement ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attachmentContext(int $actorUserId, string $identifier, int $settlementId): ?array
    {
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $settlement = ($this->settlementModel ?? new SettlementModel())
            ->findDetailForHousehold((int) $context['household']['id'], $settlementId);

        if ($settlement === null || empty($settlement['attachment_id'])) {
            return null;
        }

        $attachment = ($this->attachmentModel ?? new AttachmentModel())
            ->findForHousehold((int) $context['household']['id'], (int) $settlement['attachment_id']);

        if ($attachment === null) {
            return null;
        }

        $absolutePath = ($this->attachmentStorageService ?? new AttachmentStorageService())->absolutePath($attachment);

        if (! is_file($absolutePath)) {
            return null;
        }

        return [
            'settlement' => $settlement,
            'attachment' => $attachment,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $actorUserId, string $identifier): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'household' => $household,
            'members' => ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold((int) $household['id']),
        ];
    }

    private function normalizeMoney(string $value): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new DomainException("L'importo del settlement non e valido.");
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = is_string($value) ? trim($value) : '';

        return $resolved === '' ? null : $resolved;
    }
}
