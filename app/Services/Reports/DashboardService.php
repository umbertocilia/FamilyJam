<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Authorization\Permission;
use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Finance\ExpenseModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Pinboard\PinboardPostModel;
use App\Models\Shopping\ShoppingItemModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Balances\BalanceService;
use App\Services\Notifications\NotificationService;
use DateTimeImmutable;

final class DashboardService
{
    public function __construct(
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?BalanceService $balanceService = null,
        private readonly ?ExpenseModel $expenseModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
        private readonly ?ShoppingItemModel $shoppingItemModel = null,
        private readonly ?PinboardPostModel $pinboardPostModel = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function householdContext(int $userId, string $identifier): ?array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())->listForHousehold((int) $household['id']);
        $balanceOverview = ($this->balanceService ?? service('balanceService'))->overviewContext($userId, $identifier);
        $balancePersonal = ($this->balanceService ?? service('balanceService'))->personalContext($userId, $identifier);

        if ($balanceOverview === null || $balancePersonal === null) {
            return null;
        }

        $recentExpenses = array_slice(
            ($this->expenseModel ?? new ExpenseModel())->listForHousehold((int) $household['id']),
            0,
            6,
        );

        $upcomingChores = $this->upcomingChores((int) $household['id']);
        $urgentShoppingItems = $this->urgentShoppingItems((int) $household['id']);
        $recentPosts = array_slice(
            ($this->pinboardPostModel ?? new PinboardPostModel())->listForHousehold((int) $household['id']),
            0,
            5,
        );
        $recentNotifications = ($this->notificationService ?? service('notificationService'))
            ->drawerContext($userId, (int) $household['id'], (string) $household['slug'], 5);

        $transferRows = ! empty($household['simplify_debts'])
            ? $this->flattenTransferBuckets($balanceOverview['simplifiedTransfers'] ?? [])
            : $this->flattenTransferBuckets($balanceOverview['pairwiseBalances'] ?? []);

        return [
            'membership' => $membership,
            'household' => $household,
            'members' => $members,
            'members_count' => count($members),
            'personal_balances' => $balancePersonal['personalBalances'] ?? [],
            'primary_personal_balance' => $this->primaryPersonalBalance(
                $balancePersonal['personalBalances'] ?? [],
                (string) ($household['base_currency'] ?? '')
            ),
            'transfer_summary' => [
                'rows' => array_slice($transferRows, 0, 5),
                'mode' => ! empty($household['simplify_debts']) ? 'simplified' : 'real',
                'note' => ! empty($household['simplify_debts'])
                    ? 'Suggerimenti semplificati derivati dai saldi netti. Il ledger reale resta invariato.'
                    : 'Saldo reale derivato da spese e settlements registrati.',
            ],
            'recent_expenses' => $recentExpenses,
            'upcoming_chores' => $upcomingChores,
            'urgent_shopping_items' => $urgentShoppingItems,
            'recent_posts' => $recentPosts,
            'recent_notifications' => $recentNotifications['items'] ?? [],
            'summary' => [
                'open_transfers' => count($transferRows),
                'recent_expenses' => count($recentExpenses),
                'open_chores' => count(array_filter($upcomingChores, static fn (array $row): bool => in_array((string) $row['status'], ['pending', 'overdue'], true))),
                'urgent_items' => count($urgentShoppingItems),
                'unread_notifications' => (int) ($recentNotifications['unreadCount'] ?? 0),
            ],
            'can_manage_members' => $authorization->hasPermission($userId, $identifier, Permission::MANAGE_MEMBERS),
            'can_manage_settings' => $authorization->hasPermission($userId, $identifier, Permission::MANAGE_SETTINGS),
            'can_create_expense' => $authorization->hasPermission($userId, $identifier, Permission::CREATE_EXPENSE),
            'can_view_reports' => $authorization->hasPermission($userId, $identifier, Permission::VIEW_REPORTS),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingChores(int $householdId): array
    {
        $today = new DateTimeImmutable('today');
        $agenda = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())->listAgenda(
            $householdId,
            $today->format('Y-m-d 00:00:00'),
            $today->modify('+10 days')->format('Y-m-d 23:59:59'),
        );

        $agenda = array_values(array_filter(
            $agenda,
            static fn (array $row): bool => in_array((string) ($row['status'] ?? ''), ['pending', 'overdue'], true),
        ));

        return array_slice($agenda, 0, 6);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function urgentShoppingItems(int $householdId): array
    {
        return ($this->shoppingItemModel ?? new ShoppingItemModel())
            ->select([
                'shopping_items.*',
                'shopping_lists.name AS shopping_list_name',
                'assigned.display_name AS assigned_user_name',
            ])
            ->join('shopping_lists', 'shopping_lists.id = shopping_items.shopping_list_id', 'inner')
            ->join('users AS assigned', 'assigned.id = shopping_items.assigned_user_id', 'left')
            ->where('shopping_items.household_id', $householdId)
            ->where('shopping_items.deleted_at', null)
            ->where('shopping_items.is_purchased', 0)
            ->orderBy('FIELD(shopping_items.priority, "urgent", "high", "normal", "low")', '', false)
            ->orderBy('shopping_items.position', 'ASC')
            ->orderBy('shopping_items.id', 'ASC')
            ->findAll(6);
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $buckets
     * @return list<array<string, mixed>>
     */
    private function flattenTransferBuckets(array $buckets): array
    {
        $rows = [];

        foreach ($buckets as $currency => $items) {
            foreach ($items as $item) {
                $item['currency'] = $currency;
                $rows[] = $item;
            }
        }

        usort($rows, static fn (array $left, array $right): int => ((int) ($right['amount_cents'] ?? 0)) <=> ((int) ($left['amount_cents'] ?? 0)));

        return $rows;
    }

    /**
     * @param array<string, array<string, mixed>> $personalBalances
     * @return array<string, mixed>|null
     */
    private function primaryPersonalBalance(array $personalBalances, string $baseCurrency): ?array
    {
        if ($baseCurrency !== '' && isset($personalBalances[$baseCurrency])) {
            return $personalBalances[$baseCurrency];
        }

        return $personalBalances === [] ? null : reset($personalBalances);
    }
}
