<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Authorization\Permission;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;

final class ReportService
{
    public function __construct(
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?ExpenseModel $expenseModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?BaseConnection $db = null,
    ) {
    }

    /**
     * @param array<string, mixed> $expenseFilters
     * @param array<string, mixed> $choreFilters
     * @return array<string, mixed>|null
     */
    public function indexContext(int $userId, string $identifier, array $expenseFilters = [], array $choreFilters = []): ?array
    {
        $expense = $this->expenseReportContext($userId, $identifier, $expenseFilters);
        $chores = $this->choreReportContext($userId, $identifier, $choreFilters);

        if ($expense === null || $chores === null) {
            return null;
        }

        return [
            'membership' => $expense['membership'],
            'household' => $expense['household'],
            'expenseReport' => $expense,
            'choreReport' => $chores,
            'summary' => [
                'expense_events' => $expense['summary']['expenses_count'],
                'tracked_categories' => array_sum(array_map(static fn (array $rows): int => count($rows), $expense['byCategory'])),
                'chore_completed' => $chores['summary']['completed'],
                'chore_overdue' => $chores['summary']['overdue'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    public function expenseReportContext(int $userId, string $identifier, array $filters = []): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $normalized = $this->normalizeExpenseFilters($filters);
        $expenses = $this->filteredExpenses((int) $context['household']['id'], $normalized['from_date'], $normalized['to_date'], $normalized['member_id']);
        $members = $context['members'];
        $memberDirectory = $context['memberDirectory'];
        $currencies = $this->currenciesFromExpenses($expenses, (string) ($context['household']['base_currency'] ?? 'EUR'));
        $amountByCurrency = [];
        $categoryRows = [];
        $monthRows = [];
        $userTotals = [];
        $groupBalanceRows = [];
        $recentExpenses = array_slice($expenses, 0, 8);
        $expenseDirectory = [];
        $focusMemberId = $normalized['member_id'] ?? $userId;

        foreach ($currencies as $currency) {
            $amountByCurrency[$currency] = 0;

            foreach ($members as $member) {
                $userTotals[$currency][(int) $member['user_id']] = [
                    'currency' => $currency,
                    'user_id' => (int) $member['user_id'],
                    'display_name' => (string) ($member['display_name'] ?? $member['email']),
                    'email' => (string) ($member['email'] ?? ''),
                    'paid_amount_cents' => 0,
                    'owed_amount_cents' => 0,
                ];
            }
        }

        $db = $this->db ?? Database::connect();
        $expenseIds = array_values(array_map(static fn (array $row): int => (int) $row['id'], $expenses));

        foreach ($expenses as $expense) {
            $expenseDirectory[(int) $expense['id']] = $expense;
            $currency = (string) $expense['currency'];
            $amountCents = $this->toCents((string) $expense['total_amount']);
            $period = substr((string) $expense['expense_date'], 0, 7);
            $categoryKey = $currency . '|' . ((string) ($expense['category_name'] ?? ui_text('category.uncategorized')));
            $monthKey = $currency . '|' . $period;

            $amountByCurrency[$currency] = ($amountByCurrency[$currency] ?? 0) + $amountCents;
            $categoryRows[$categoryKey] ??= [
                'currency' => $currency,
                'category_name' => (string) ($expense['category_name'] ?? 'Uncategorized'),
                'category_color' => (string) ($expense['category_color'] ?? ''),
                'amount_cents' => 0,
                'expenses_count' => 0,
            ];
            $categoryRows[$categoryKey]['amount_cents'] += $amountCents;
            $categoryRows[$categoryKey]['expenses_count']++;

            $monthRows[$monthKey] ??= [
                'currency' => $currency,
                'period' => $period,
                'amount_cents' => 0,
                'expenses_count' => 0,
            ];
            $monthRows[$monthKey]['amount_cents'] += $amountCents;
            $monthRows[$monthKey]['expenses_count']++;
        }

        if ($expenseIds !== []) {
            $payerRows = $db->table('expense_payers')
                ->select(['expense_payers.expense_id', 'expenses.currency', 'expense_payers.user_id', 'expense_payers.amount_paid'])
                ->join('expenses', 'expenses.id = expense_payers.expense_id', 'inner')
                ->whereIn('expense_payers.expense_id', $expenseIds)
                ->get()
                ->getResultArray();

            foreach ($payerRows as $row) {
                $currency = (string) $row['currency'];
                $userId = (int) $row['user_id'];

                if (! isset($userTotals[$currency][$userId])) {
                    $member = $memberDirectory[$userId] ?? ['display_name' => 'User #' . $userId, 'email' => ''];
                    $userTotals[$currency][$userId] = [
                        'currency' => $currency,
                        'user_id' => $userId,
                        'display_name' => (string) ($member['display_name'] ?? $member['email']),
                        'email' => (string) ($member['email'] ?? ''),
                        'paid_amount_cents' => 0,
                        'owed_amount_cents' => 0,
                    ];
                }

                $userTotals[$currency][$userId]['paid_amount_cents'] += $this->toCents((string) $row['amount_paid']);

                if ($userId === $focusMemberId) {
                    $expense = $expenseDirectory[(int) $row['expense_id']] ?? null;
                    $groupName = (string) (($expense['expense_group_name'] ?? null) ?: ui_text('expense.label.group.general'));
                    $groupKey = $currency . '|' . $groupName;
                    $groupBalanceRows[$groupKey] ??= [
                        'currency' => $currency,
                        'group_name' => $groupName,
                        'net_amount_cents' => 0,
                    ];
                    $groupBalanceRows[$groupKey]['net_amount_cents'] += $this->toCents((string) $row['amount_paid']);
                }
            }

            $splitRows = $db->table('expense_splits')
                ->select(['expense_splits.expense_id', 'expenses.currency', 'expense_splits.user_id', 'expense_splits.owed_amount'])
                ->join('expenses', 'expenses.id = expense_splits.expense_id', 'inner')
                ->whereIn('expense_splits.expense_id', $expenseIds)
                ->where('expense_splits.is_excluded', 0)
                ->get()
                ->getResultArray();

            foreach ($splitRows as $row) {
                $currency = (string) $row['currency'];
                $userId = (int) $row['user_id'];

                if (! isset($userTotals[$currency][$userId])) {
                    $member = $memberDirectory[$userId] ?? ['display_name' => 'User #' . $userId, 'email' => ''];
                    $userTotals[$currency][$userId] = [
                        'currency' => $currency,
                        'user_id' => $userId,
                        'display_name' => (string) ($member['display_name'] ?? $member['email']),
                        'email' => (string) ($member['email'] ?? ''),
                        'paid_amount_cents' => 0,
                        'owed_amount_cents' => 0,
                    ];
                }

                $userTotals[$currency][$userId]['owed_amount_cents'] += $this->toCents((string) $row['owed_amount']);

                if ($userId === $focusMemberId) {
                    $expense = $expenseDirectory[(int) $row['expense_id']] ?? null;
                    $groupName = (string) (($expense['expense_group_name'] ?? null) ?: ui_text('expense.label.group.general'));
                    $groupKey = $currency . '|' . $groupName;
                    $groupBalanceRows[$groupKey] ??= [
                        'currency' => $currency,
                        'group_name' => $groupName,
                        'net_amount_cents' => 0,
                    ];
                    $groupBalanceRows[$groupKey]['net_amount_cents'] -= $this->toCents((string) $row['owed_amount']);
                }
            }
        }

        if ($focusMemberId > 0) {
            $settlementRows = $db->table('settlements')
                ->select([
                    'settlements.currency',
                    'settlements.amount',
                    'settlements.from_user_id',
                    'settlements.to_user_id',
                    'expense_groups.name AS expense_group_name',
                ])
                ->join('expense_groups', 'expense_groups.id = settlements.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
                ->where('settlements.household_id', (int) $context['household']['id'])
                ->where('settlements.settlement_date >=', $normalized['from_date'])
                ->where('settlements.settlement_date <=', $normalized['to_date'])
                ->groupStart()
                    ->where('settlements.from_user_id', $focusMemberId)
                    ->orWhere('settlements.to_user_id', $focusMemberId)
                ->groupEnd()
                ->get()
                ->getResultArray();

            foreach ($settlementRows as $row) {
                $currency = (string) $row['currency'];
                $groupName = (string) (($row['expense_group_name'] ?? null) ?: ui_text('expense.label.group.general'));
                $groupKey = $currency . '|' . $groupName;
                $groupBalanceRows[$groupKey] ??= [
                    'currency' => $currency,
                    'group_name' => $groupName,
                    'net_amount_cents' => 0,
                ];

                if ((int) $row['from_user_id'] === $focusMemberId) {
                    $groupBalanceRows[$groupKey]['net_amount_cents'] += $this->toCents((string) $row['amount']);
                }

                if ((int) $row['to_user_id'] === $focusMemberId) {
                    $groupBalanceRows[$groupKey]['net_amount_cents'] -= $this->toCents((string) $row['amount']);
                }
            }
        }

        $byCategory = $this->finalizeCurrencyBuckets($categoryRows, $amountByCurrency, static fn (array $left, array $right): int => $right['amount_cents'] <=> $left['amount_cents']);
        $byMonth = $this->finalizeCurrencyBuckets($monthRows, $amountByCurrency, static fn (array $left, array $right): int => strcmp((string) $left['period'], (string) $right['period']));
        $byUser = [];
        $topSpenders = [];

        foreach ($userTotals as $currency => $rows) {
            $finalRows = array_map(function (array $row): array {
                $row['paid_amount'] = $this->fromCents((int) $row['paid_amount_cents']);
                $row['owed_amount'] = $this->fromCents((int) $row['owed_amount_cents']);
                $row['net_amount_cents'] = (int) $row['paid_amount_cents'] - (int) $row['owed_amount_cents'];
                $row['net_amount'] = $this->fromCents((int) $row['net_amount_cents']);

                return $row;
            }, array_values($rows));

            usort($finalRows, static fn (array $left, array $right): int => $right['paid_amount_cents'] <=> $left['paid_amount_cents'] ?: $right['owed_amount_cents'] <=> $left['owed_amount_cents']);
            $byUser[$currency] = $finalRows;
            $topSpenders[$currency] = array_slice($finalRows, 0, 5);
        }

        $balancesByGroup = [];
        $overallByCurrency = [];
        foreach ($groupBalanceRows as $row) {
            $currency = (string) $row['currency'];
            $netCents = (int) $row['net_amount_cents'];
            $direction = $netCents > 0 ? 'gets_back' : ($netCents < 0 ? 'owes' : 'settled');
            $balancesByGroup[$currency][] = [
                'currency' => $currency,
                'group_name' => (string) $row['group_name'],
                'net_amount_cents' => $netCents,
                'net_amount' => $this->fromCents($netCents),
                'direction' => $direction,
            ];
            $overallByCurrency[$currency] = ($overallByCurrency[$currency] ?? 0) + $netCents;
        }

        foreach ($balancesByGroup as &$rows) {
            usort($rows, static fn (array $left, array $right): int => abs((int) $right['net_amount_cents']) <=> abs((int) $left['net_amount_cents']));
        }
        unset($rows);

        return array_merge($context, [
            'filters' => $normalized,
            'summary' => [
                'expenses_count' => count($expenses),
                'amount_by_currency' => array_map(
                    fn (string $currency, int $amountCents): array => [
                        'currency' => $currency,
                        'amount' => $this->fromCents($amountCents),
                        'amount_cents' => $amountCents,
                    ],
                    array_keys($amountByCurrency),
                    array_values($amountByCurrency),
                ),
            ],
            'byCategory' => $byCategory,
            'byMonth' => $byMonth,
            'byUser' => $byUser,
            'topSpenders' => $topSpenders,
            'recentExpenses' => $recentExpenses,
            'balanceByGroup' => $balancesByGroup,
            'overallBalance' => array_map(
                fn (string $currency, int $netCents): array => [
                    'currency' => $currency,
                    'net_amount_cents' => $netCents,
                    'net_amount' => $this->fromCents($netCents),
                    'direction' => $netCents > 0 ? 'gets_back' : ($netCents < 0 ? 'owes' : 'settled'),
                ],
                array_keys($overallByCurrency),
                array_values($overallByCurrency),
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    public function choreReportContext(int $userId, string $identifier, array $filters = []): ?array
    {
        $context = $this->resolveContext($userId, $identifier);

        if ($context === null) {
            return null;
        }

        $normalized = $this->normalizeChoreFilters($filters);
        $rows = $this->filteredChoreOccurrences((int) $context['household']['id'], $normalized['from_date'], $normalized['to_date'], $normalized['assigned_user_id']);
        $memberDirectory = $context['memberDirectory'];
        $byUser = [];
        $byDay = [];
        $statusCounts = [
            'pending' => 0,
            'completed' => 0,
            'skipped' => 0,
            'overdue' => 0,
        ];
        $pointsTotal = 0;

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $dateKey = substr((string) $row['due_at'], 0, 10);
            $assignedUserId = (int) ($row['assigned_user_id'] ?? 0);
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            $pointsTotal += (int) ($row['points_awarded'] ?? 0);

            $byDay[$dateKey] ??= [
                'date' => $dateKey,
                'completed' => 0,
                'skipped' => 0,
                'overdue' => 0,
                'pending' => 0,
            ];
            $byDay[$dateKey][$status] = ($byDay[$dateKey][$status] ?? 0) + 1;

            if ($assignedUserId <= 0) {
                continue;
            }

            $byUser[$assignedUserId] ??= [
                'user_id' => $assignedUserId,
                'display_name' => (string) (($memberDirectory[$assignedUserId]['display_name'] ?? $row['assigned_user_name'] ?? 'User #' . $assignedUserId)),
                'email' => (string) ($memberDirectory[$assignedUserId]['email'] ?? ''),
                'completed_count' => 0,
                'skipped_count' => 0,
                'overdue_count' => 0,
                'pending_count' => 0,
                'points_total' => 0,
                'minutes_total' => 0,
            ];
            $byUser[$assignedUserId][$status . '_count'] = ((int) $byUser[$assignedUserId][$status . '_count']) + 1;

            if ($status === 'completed') {
                $byUser[$assignedUserId]['points_total'] += (int) ($row['points_awarded'] ?? 0);
                $byUser[$assignedUserId]['minutes_total'] += (int) ($row['estimated_minutes'] ?? 0);
            }
        }

        $userRows = array_values($byUser);
        usort($userRows, static fn (array $left, array $right): int => $right['points_total'] <=> $left['points_total'] ?: $right['completed_count'] <=> $left['completed_count']);
        $timelineRows = array_values($byDay);
        usort($timelineRows, static fn (array $left, array $right): int => strcmp((string) $left['date'], (string) $right['date']));

        return array_merge($context, [
            'filters' => $normalized,
            'summary' => [
                'occurrences_count' => count($rows),
                'completed' => $statusCounts['completed'],
                'skipped' => $statusCounts['skipped'],
                'overdue' => $statusCounts['overdue'],
                'pending' => $statusCounts['pending'],
                'points_total' => $pointsTotal,
            ],
            'byUser' => $userRows,
            'statusBreakdown' => $statusCounts,
            'timeline' => $timelineRows,
            'recentOccurrences' => array_slice($rows, 0, 10),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveContext(int $userId, string $identifier): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');

        if (! $authorization->hasPermission($userId, $identifier, Permission::VIEW_REPORTS)) {
            return null;
        }

        $membership = $authorization->membershipByIdentifier($userId, $identifier);

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
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{months: int, member_id: int|null, from_date: string, to_date: string}
     */
    private function normalizeExpenseFilters(array $filters): array
    {
        $months = (int) ($filters['months'] ?? 6);
        $months = in_array($months, [1, 3, 6, 12], true) ? $months : 6;
        $memberId = is_numeric($filters['member_id'] ?? null) ? (int) $filters['member_id'] : null;
        $end = new DateTimeImmutable('today');
        $start = $end->modify('first day of this month')->modify('-' . ($months - 1) . ' months');

        return [
            'months' => $months,
            'member_id' => $memberId !== null && $memberId > 0 ? $memberId : null,
            'from_date' => $start->format('Y-m-d'),
            'to_date' => $end->format('Y-m-d'),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{days: int, assigned_user_id: int|null, from_date: string, to_date: string}
     */
    private function normalizeChoreFilters(array $filters): array
    {
        $days = (int) ($filters['days'] ?? 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $assignedUserId = is_numeric($filters['assigned_user_id'] ?? null) ? (int) $filters['assigned_user_id'] : null;
        $end = new DateTimeImmutable('today');
        $start = $end->modify('-' . max(0, $days - 1) . ' days');

        return [
            'days' => $days,
            'assigned_user_id' => $assignedUserId !== null && $assignedUserId > 0 ? $assignedUserId : null,
            'from_date' => $start->format('Y-m-d'),
            'to_date' => $end->format('Y-m-d'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function filteredExpenses(int $householdId, string $fromDate, string $toDate, ?int $memberId): array
    {
        helper('ui');
        $builder = ($this->expenseModel ?? new ExpenseModel())
            ->select([
                'expenses.*',
                'expense_categories.name AS category_name',
                'expense_categories.color AS category_color',
                'expense_categories.code AS category_code',
                'expense_categories.is_system AS category_is_system',
                'expense_groups.name AS expense_group_name',
                'expense_groups.color AS expense_group_color',
                'creator.display_name AS created_by_name',
            ])
            ->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left')
            ->join('expense_groups', 'expense_groups.id = expenses.expense_group_id AND expense_groups.deleted_at IS NULL', 'left')
            ->join('users AS creator', 'creator.id = expenses.created_by', 'left')
            ->where('expenses.household_id', $householdId)
            ->where('expenses.deleted_at', null)
            ->whereIn('expenses.status', ['active', 'edited', 'disputed', 'posted'])
            ->where('expenses.expense_date >=', $fromDate)
            ->where('expenses.expense_date <=', $toDate);

        if ($memberId !== null) {
            $builder->groupStart()
                ->whereIn('expenses.id', static function ($subQuery) use ($memberId): void {
                    $subQuery->select('expense_payers.expense_id')
                        ->from('expense_payers')
                        ->where('expense_payers.user_id', $memberId);
                })
                ->orWhereIn('expenses.id', static function ($subQuery) use ($memberId): void {
                    $subQuery->select('expense_splits.expense_id')
                        ->from('expense_splits')
                        ->where('expense_splits.user_id', $memberId)
                        ->where('expense_splits.is_excluded', 0);
                })
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('expenses.expense_date', 'DESC')
            ->orderBy('expenses.created_at', 'DESC')
            ->findAll();

        return array_map(function (array $row): array {
            $row['category_name'] = ui_category_name(
                isset($row['category_code']) ? (string) $row['category_code'] : null,
                isset($row['category_name']) ? (string) $row['category_name'] : null,
                ! empty($row['category_is_system']),
            );

            return $row;
        }, $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function filteredChoreOccurrences(int $householdId, string $fromDate, string $toDate, ?int $assignedUserId): array
    {
        $builder = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->select([
                'chore_occurrences.*',
                'chores.title AS chore_title',
                'chores.estimated_minutes',
                'assigned.display_name AS assigned_user_name',
            ])
            ->join('chores', 'chores.id = chore_occurrences.chore_id', 'inner')
            ->join('users AS assigned', 'assigned.id = chore_occurrences.assigned_user_id', 'left')
            ->where('chore_occurrences.household_id', $householdId)
            ->where('chore_occurrences.due_at >=', $fromDate . ' 00:00:00')
            ->where('chore_occurrences.due_at <=', $toDate . ' 23:59:59');

        if ($assignedUserId !== null) {
            $builder->where('chore_occurrences.assigned_user_id', $assignedUserId);
        }

        return $builder
            ->orderBy('chore_occurrences.due_at', 'DESC')
            ->orderBy('chore_occurrences.id', 'DESC')
            ->findAll();
    }

    /**
     * @param list<array<string, mixed>> $expenses
     * @return list<string>
     */
    private function currenciesFromExpenses(array $expenses, string $fallback): array
    {
        $currencies = [];

        foreach ($expenses as $expense) {
            $currency = strtoupper((string) ($expense['currency'] ?? ''));

            if ($currency !== '' && ! in_array($currency, $currencies, true)) {
                $currencies[] = $currency;
            }
        }

        if ($currencies === []) {
            $currencies[] = strtoupper($fallback !== '' ? $fallback : 'EUR');
        }

        return $currencies;
    }

    /**
     * @param array<string, array<string, mixed>> $rows
     * @param array<string, int> $amountByCurrency
     * @return array<string, list<array<string, mixed>>>
     */
    private function finalizeCurrencyBuckets(array $rows, array $amountByCurrency, callable $sorter): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $currency = (string) $row['currency'];
            $row['amount'] = $this->fromCents((int) $row['amount_cents']);
            $row['share_percent'] = ($amountByCurrency[$currency] ?? 0) > 0
                ? round(((int) $row['amount_cents'] / (int) $amountByCurrency[$currency]) * 100, 2)
                : 0.0;
            $grouped[$currency][] = $row;
        }

        foreach ($grouped as &$currencyRows) {
            usort($currencyRows, $sorter);
        }

        return $grouped;
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
