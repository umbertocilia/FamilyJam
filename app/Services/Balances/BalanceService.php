<?php

declare(strict_types=1);

namespace App\Services\Balances;

use App\Models\Finance\ExpenseModel;
use App\Models\Finance\ExpensePayerModel;
use App\Models\Finance\ExpenseSplitModel;
use App\Models\Finance\SettlementModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Authorization\HouseholdAuthorizationService;

final class BalanceService
{
    public function __construct(
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ExpenseModel $expenseModel = null,
        private readonly ?ExpensePayerModel $expensePayerModel = null,
        private readonly ?ExpenseSplitModel $expenseSplitModel = null,
        private readonly ?SettlementModel $settlementModel = null,
        private readonly ?DebtSimplificationService $debtSimplificationService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function overviewContext(int $actorUserId, string $identifier): ?array
    {
        helper('ui');
        $context = $this->resolveContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $ledger = $this->buildLedger((int) $context['household']['id'], $context['memberDirectory']);

        if ($ledger === null) {
            return null;
        }

        if (! $context['isSimplificationEnabled']) {
            $ledger['simplifiedTransfers'] = [];
        }

        return array_merge($context, $ledger, [
            'personalUserId' => $actorUserId,
            'realLedgerNote' => ui_locale() === 'it'
                ? 'Il ledger reale deriva da spese e settlement registrati. I saldi netti sono la fonte di verita contabile.'
                : 'The real ledger comes from recorded expenses and settlements. Net balances are the source of truth.',
            'simplifiedLedgerNote' => ui_locale() === 'it'
                ? 'I suggerimenti semplificati comprimono i trasferimenti partendo dai saldi netti, senza modificare il ledger reale.'
                : 'Simplified suggestions compress transfers from net balances without changing the real ledger.',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function personalContext(int $actorUserId, string $identifier, ?int $targetUserId = null): ?array
    {
        $context = $this->overviewContext($actorUserId, $identifier);

        if ($context === null) {
            return null;
        }

        $resolvedUserId = $targetUserId ?? $actorUserId;
        $personalBalances = [];
        $personalPairwise = [];
        $personalSimplified = [];
        $personalGroupBalances = [];

        foreach ($context['netBalances'] as $currency => $rows) {
            foreach ($rows as $row) {
                if ((int) $row['user_id'] === $resolvedUserId) {
                    $personalBalances[$currency] = $row;
                }
            }
        }

        foreach ($context['pairwiseBalances'] as $currency => $rows) {
            $personalPairwise[$currency] = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) $row['from_user_id'] === $resolvedUserId || (int) $row['to_user_id'] === $resolvedUserId,
            ));
        }

        foreach ($context['simplifiedTransfers'] as $currency => $rows) {
            $personalSimplified[$currency] = array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int) $row['from_user_id'] === $resolvedUserId || (int) $row['to_user_id'] === $resolvedUserId,
            ));
        }

        foreach ($context['groupBalances'] as $group) {
            foreach ($group['netBalances'] as $currency => $rows) {
                foreach ($rows as $row) {
                    if ((int) $row['user_id'] !== $resolvedUserId) {
                        continue;
                    }

                    $personalGroupBalances[$currency][] = [
                        'group_id' => $group['group_id'],
                        'group_name' => $group['group_name'],
                        'group_color' => $group['group_color'],
                        'currency' => $currency,
                        'net_amount' => $row['net_amount'],
                        'net_amount_cents' => $row['net_amount_cents'],
                        'direction' => $row['direction'],
                    ];
                }
            }
        }

        foreach ($personalGroupBalances as &$rows) {
            usort($rows, static fn (array $left, array $right): int => abs((int) $right['net_amount_cents']) <=> abs((int) $left['net_amount_cents']));
        }
        unset($rows);

        return array_merge($context, [
            'personalUserId' => $resolvedUserId,
            'personalMember' => $context['memberDirectory'][$resolvedUserId] ?? null,
            'personalBalances' => $personalBalances,
            'personalPairwiseBalances' => $personalPairwise,
            'personalSimplifiedTransfers' => $personalSimplified,
            'personalGroupBalances' => $personalGroupBalances,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pairwiseContext(int $actorUserId, string $identifier): ?array
    {
        return $this->overviewContext($actorUserId, $identifier);
    }

    /**
     * @param array<string, mixed> $expense
     * @param list<array<string, mixed>> $payers
     * @param list<array<string, mixed>> $splits
     * @return array<int, int>
     */
    public function expenseEventBalances(array $expense, array $payers, array $splits): array
    {
        $balances = [];

        foreach ($payers as $payer) {
            $userId = (int) $payer['user_id'];
            $balances[$userId] = ($balances[$userId] ?? 0) + $this->toCents((string) $payer['amount_paid']);
        }

        foreach ($splits as $split) {
            if (! empty($split['is_excluded'])) {
                continue;
            }

            $userId = (int) $split['user_id'];
            $balances[$userId] = ($balances[$userId] ?? 0) - $this->toCents((string) $split['owed_amount']);
        }

        return $balances;
    }

    /**
     * @param array<string, mixed> $settlement
     * @return array<int, int>
     */
    public function settlementEventBalances(array $settlement): array
    {
        $amountCents = $this->toCents((string) $settlement['amount']);

        return [
            (int) $settlement['from_user_id'] => $amountCents,
            (int) $settlement['to_user_id'] => -$amountCents,
        ];
    }

    /**
     * @param array<int, int> $eventBalances
     * @return list<array<string, mixed>>
     */
    public function pairwiseEdgesFromEventBalances(array $eventBalances, string $currency, string $sourceType, int $sourceId): array
    {
        $creditors = [];
        $debtors = [];

        foreach ($eventBalances as $userId => $cents) {
            if ($cents > 0) {
                $creditors[] = ['user_id' => (int) $userId, 'remaining_cents' => $cents];
            } elseif ($cents < 0) {
                $debtors[] = ['user_id' => (int) $userId, 'remaining_cents' => abs($cents)];
            }
        }

        usort($creditors, static fn (array $left, array $right): int => $left['user_id'] <=> $right['user_id']);
        usort($debtors, static fn (array $left, array $right): int => $left['user_id'] <=> $right['user_id']);

        $edges = [];
        $creditorIndex = 0;
        $debtorIndex = 0;

        while (isset($debtors[$debtorIndex], $creditors[$creditorIndex])) {
            $transferCents = min($debtors[$debtorIndex]['remaining_cents'], $creditors[$creditorIndex]['remaining_cents']);

            $edges[] = [
                'currency' => $currency,
                'from_user_id' => $debtors[$debtorIndex]['user_id'],
                'to_user_id' => $creditors[$creditorIndex]['user_id'],
                'amount' => $this->fromCents($transferCents),
                'amount_cents' => $transferCents,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ];

            $debtors[$debtorIndex]['remaining_cents'] -= $transferCents;
            $creditors[$creditorIndex]['remaining_cents'] -= $transferCents;

            if ($debtors[$debtorIndex]['remaining_cents'] === 0) {
                $debtorIndex++;
            }

            if ($creditors[$creditorIndex]['remaining_cents'] === 0) {
                $creditorIndex++;
            }
        }

        return $edges;
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return array<string, array<int, int>>
     */
    public function aggregateNetBalanceMap(array $events): array
    {
        $map = [];

        foreach ($events as $event) {
            $currency = (string) $event['currency'];
            $map[$currency] ??= [];

            foreach ($event['balances'] as $userId => $cents) {
                $map[$currency][(int) $userId] = ($map[$currency][(int) $userId] ?? 0) + (int) $cents;
            }
        }

        return $map;
    }

    /**
     * @param list<array<string, mixed>> $edges
     * @param array<int, array<string, mixed>> $memberDirectory
     * @return array<string, list<array<string, mixed>>>
     */
    public function compressPairwiseEdges(array $edges, array $memberDirectory = []): array
    {
        $pairMap = [];

        foreach ($edges as $edge) {
            $currency = (string) $edge['currency'];
            $fromUserId = (int) $edge['from_user_id'];
            $toUserId = (int) $edge['to_user_id'];
            $amountCents = (int) $edge['amount_cents'];
            $lowUserId = min($fromUserId, $toUserId);
            $highUserId = max($fromUserId, $toUserId);
            $pairKey = $currency . ':' . $lowUserId . ':' . $highUserId;
            $pairMap[$pairKey] ??= 0;

            if ($fromUserId === $lowUserId) {
                $pairMap[$pairKey] += $amountCents;
            } else {
                $pairMap[$pairKey] -= $amountCents;
            }
        }

        $compressed = [];

        foreach ($pairMap as $pairKey => $signedCents) {
            if ($signedCents === 0) {
                continue;
            }

            [$currency, $lowUserId, $highUserId] = explode(':', $pairKey);
            $fromUserId = $signedCents > 0 ? (int) $lowUserId : (int) $highUserId;
            $toUserId = $signedCents > 0 ? (int) $highUserId : (int) $lowUserId;
            $amountCents = abs($signedCents);

            $compressed[$currency][] = [
                'currency' => $currency,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $this->fromCents($amountCents),
                'amount_cents' => $amountCents,
                'from_user_name' => $memberDirectory[$fromUserId]['display_name'] ?? $memberDirectory[$fromUserId]['email'] ?? 'User #' . $fromUserId,
                'to_user_name' => $memberDirectory[$toUserId]['display_name'] ?? $memberDirectory[$toUserId]['email'] ?? 'User #' . $toUserId,
            ];
        }

        foreach ($compressed as &$rows) {
            usort($rows, static fn (array $left, array $right): int => $right['amount_cents'] <=> $left['amount_cents'] ?: $left['from_user_id'] <=> $right['from_user_id'] ?: $left['to_user_id'] <=> $right['to_user_id']);
        }

        return $compressed;
    }

    /**
     * @param array<string, array<int, int>> $netBalanceMap
     * @param array<int, array<string, mixed>> $memberDirectory
     * @return array<string, list<array<string, mixed>>>
     */
    public function decorateNetBalances(array $netBalanceMap, array $memberDirectory = []): array
    {
        $rows = [];

        foreach ($netBalanceMap as $currency => $balances) {
            foreach ($memberDirectory as $userId => $member) {
                $balances[(int) $userId] = $balances[(int) $userId] ?? 0;
            }

            foreach ($balances as $userId => $cents) {
                $rows[$currency][] = [
                    'currency' => $currency,
                    'user_id' => (int) $userId,
                    'display_name' => $memberDirectory[(int) $userId]['display_name'] ?? $memberDirectory[(int) $userId]['email'] ?? 'User #' . $userId,
                    'email' => $memberDirectory[(int) $userId]['email'] ?? null,
                    'net_amount' => $this->fromCents($cents),
                    'net_amount_cents' => $cents,
                    'direction' => $cents > 0 ? 'gets_back' : ($cents < 0 ? 'owes' : 'settled'),
                ];
            }

            usort($rows[$currency], static fn (array $left, array $right): int => $right['net_amount_cents'] <=> $left['net_amount_cents'] ?: $left['user_id'] <=> $right['user_id']);
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $memberDirectory
     * @return array<string, mixed>|null
     */
    private function buildLedger(int $householdId, array $memberDirectory): ?array
    {
        $expenseModel = $this->expenseModel ?? new ExpenseModel();
        $expensePayerModel = $this->expensePayerModel ?? new ExpensePayerModel();
        $expenseSplitModel = $this->expenseSplitModel ?? new ExpenseSplitModel();
        $settlementModel = $this->settlementModel ?? new SettlementModel();
        $debtSimplifier = $this->debtSimplificationService ?? new DebtSimplificationService();

        $expenses = $expenseModel->listLedgerEligibleForHousehold($householdId);
        $expenseIds = array_values(array_map(static fn (array $expense): int => (int) $expense['id'], $expenses));
        $payersByExpenseId = $expensePayerModel->listForExpenseIds($expenseIds);
        $splitsByExpenseId = $expenseSplitModel->listForExpenseIds($expenseIds);
        $settlements = $settlementModel->listForHousehold($householdId);

        $events = [];
        $realEdges = [];
        $groupNetBalanceMap = [];
        $groupEdgeMap = [];

        foreach ($expenses as $expense) {
            $balances = $this->expenseEventBalances(
                $expense,
                $payersByExpenseId[(int) $expense['id']] ?? [],
                $splitsByExpenseId[(int) $expense['id']] ?? [],
            );

            $events[] = [
                'currency' => (string) $expense['currency'],
                'source_type' => 'expense',
                'source_id' => (int) $expense['id'],
                'balances' => $balances,
            ];

            $groupKey = $this->groupKey(
                isset($expense['expense_group_id']) && $expense['expense_group_id'] !== null ? (int) $expense['expense_group_id'] : null,
                isset($expense['expense_group_name']) ? (string) $expense['expense_group_name'] : null,
                isset($expense['expense_group_color']) ? (string) $expense['expense_group_color'] : null,
            );

            $this->addGroupBalances(
                $groupNetBalanceMap,
                $groupKey,
                (string) $expense['currency'],
                $balances,
            );
            $edges = $this->pairwiseEdgesFromEventBalances($balances, (string) $expense['currency'], 'expense', (int) $expense['id']);
            $groupEdgeMap[$groupKey['map_key']] = array_merge($groupEdgeMap[$groupKey['map_key']] ?? [], $edges);
            $realEdges = array_merge($realEdges, $edges);
        }

        foreach ($settlements as $settlement) {
            $balances = $this->settlementEventBalances($settlement);

            $events[] = [
                'currency' => (string) $settlement['currency'],
                'source_type' => 'settlement',
                'source_id' => (int) $settlement['id'],
                'balances' => $balances,
            ];

            $groupKey = $this->groupKey(
                isset($settlement['expense_group_id']) && $settlement['expense_group_id'] !== null ? (int) $settlement['expense_group_id'] : null,
                isset($settlement['expense_group_name']) ? (string) $settlement['expense_group_name'] : null,
                isset($settlement['expense_group_color']) ? (string) $settlement['expense_group_color'] : null,
            );

            $this->addGroupBalances(
                $groupNetBalanceMap,
                $groupKey,
                (string) $settlement['currency'],
                $balances,
            );
            $edges = $this->pairwiseEdgesFromEventBalances($balances, (string) $settlement['currency'], 'settlement', (int) $settlement['id']);
            $groupEdgeMap[$groupKey['map_key']] = array_merge($groupEdgeMap[$groupKey['map_key']] ?? [], $edges);
            $realEdges = array_merge($realEdges, $edges);
        }

        $netBalanceMap = $this->aggregateNetBalanceMap($events);

        return [
            'events' => $events,
            'netBalanceMap' => $netBalanceMap,
            'netBalances' => $this->decorateNetBalances($netBalanceMap, $memberDirectory),
            'pairwiseBalances' => $this->compressPairwiseEdges($realEdges, $memberDirectory),
            'simplifiedTransfers' => $debtSimplifier->simplify($netBalanceMap, $memberDirectory),
            'groupBalances' => $this->decorateGroupBalances($groupNetBalanceMap, $groupEdgeMap, $memberDirectory),
            'expensesForLedger' => $expenses,
            'settlementsForLedger' => $settlements,
        ];
    }

    /**
     * @return array{map_key:string,group_id:int|null,group_name:string,group_color:string|null}
     */
    private function groupKey(?int $groupId, ?string $groupName, ?string $groupColor): array
    {
        $resolvedName = trim((string) $groupName);
        if ($resolvedName === '') {
            $resolvedName = ui_text('expense.label.group.general');
        }

        return [
            'map_key' => $groupId === null ? 'general' : 'group:' . $groupId,
            'group_id' => $groupId,
            'group_name' => $resolvedName,
            'group_color' => $groupColor !== null && trim($groupColor) !== '' ? $groupColor : null,
        ];
    }

    /**
     * @param array<string, array{map_key:string,group_id:int|null,group_name:string,group_color:string|null}> $groupKey
     * @param array<int,int> $balances
     */
    private function addGroupBalances(array &$groupNetBalanceMap, array $groupKey, string $currency, array $balances): void
    {
        $mapKey = $groupKey['map_key'];
        $groupNetBalanceMap[$mapKey] ??= [
            'group_id' => $groupKey['group_id'],
            'group_name' => $groupKey['group_name'],
            'group_color' => $groupKey['group_color'],
            'currencies' => [],
        ];
        $groupNetBalanceMap[$mapKey]['currencies'][$currency] ??= [];

        foreach ($balances as $userId => $cents) {
            $groupNetBalanceMap[$mapKey]['currencies'][$currency][(int) $userId]
                = ($groupNetBalanceMap[$mapKey]['currencies'][$currency][(int) $userId] ?? 0) + (int) $cents;
        }
    }

    /**
     * @param array<string, array{group_id:int|null,group_name:string,group_color:string|null,currencies:array<string,array<int,int>>}> $groupNetBalanceMap
     * @param array<string, list<array<string, mixed>>> $groupEdgeMap
     * @param array<int, array<string, mixed>> $memberDirectory
     * @return list<array<string, mixed>>
     */
    private function decorateGroupBalances(array $groupNetBalanceMap, array $groupEdgeMap, array $memberDirectory = []): array
    {
        $debtSimplifier = $this->debtSimplificationService ?? new DebtSimplificationService();
        $groups = [];

        foreach ($groupNetBalanceMap as $mapKey => $group) {
            $netBalances = [];

            foreach ($group['currencies'] as $currency => $balances) {
                foreach ($memberDirectory as $userId => $_member) {
                    $balances[(int) $userId] = $balances[(int) $userId] ?? 0;
                }

                $rows = [];
                foreach ($balances as $userId => $cents) {
                    $rows[] = [
                        'currency' => $currency,
                        'user_id' => (int) $userId,
                        'display_name' => $memberDirectory[(int) $userId]['display_name'] ?? $memberDirectory[(int) $userId]['email'] ?? 'User #' . $userId,
                        'email' => $memberDirectory[(int) $userId]['email'] ?? null,
                        'net_amount' => $this->fromCents((int) $cents),
                        'net_amount_cents' => (int) $cents,
                        'direction' => $cents > 0 ? 'gets_back' : ($cents < 0 ? 'owes' : 'settled'),
                    ];
                }

                usort($rows, static fn (array $left, array $right): int => $right['net_amount_cents'] <=> $left['net_amount_cents'] ?: $left['user_id'] <=> $right['user_id']);
                $netBalances[$currency] = $rows;
            }

            $groups[] = [
                'group_id' => $group['group_id'],
                'group_name' => $group['group_name'],
                'group_color' => $group['group_color'],
                'netBalances' => $netBalances,
                'pairwiseBalances' => $this->compressPairwiseEdges($groupEdgeMap[$mapKey] ?? [], $memberDirectory),
                'simplifiedTransfers' => $debtSimplifier->simplify($group['currencies'], $memberDirectory),
            ];
        }

        usort($groups, static fn (array $left, array $right): int => strcmp((string) $left['group_name'], (string) $right['group_name']));

        return $groups;
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

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold((int) $household['id']);
        $memberDirectory = [];

        foreach ($members as $member) {
            $memberDirectory[(int) $member['user_id']] = $member;
        }

        return [
            'membership' => $membership,
            'household' => $household,
            'members' => $members,
            'memberDirectory' => $memberDirectory,
            'isSimplificationEnabled' => ! empty($household['simplify_debts']),
        ];
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) str_replace(',', '.', $amount)) * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
