<?php

declare(strict_types=1);

namespace App\Services\Expenses;

use App\Models\Finance\ExpenseCategoryModel;
use App\Models\Finance\ExpenseGroupMemberModel;
use App\Models\Finance\ExpenseGroupModel;
use DomainException;

final class ExpenseValidationService
{
    public function __construct(
        private readonly ?SplitCalculationService $splitCalculationService = null,
        private readonly ?ExpenseCategoryModel $expenseCategoryModel = null,
        private readonly ?ExpenseGroupModel $expenseGroupModel = null,
        private readonly ?ExpenseGroupMemberModel $expenseGroupMemberModel = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array<string, mixed>> $members
     * @return array{payers:list<array<string,mixed>>,splits:list<array<string,mixed>>,participants:list<array<string,mixed>>,total_amount:string}
     */
    public function validateAndNormalize(int $householdId, array $payload, array $members): array
    {
        helper('ui');
        $memberIds = array_map(static fn (array $member): int => (int) $member['user_id'], $members);
        $uniqueMemberIds = array_values(array_unique($memberIds));
        $totalAmount = $this->normalizeMoney((string) ($payload['total_amount'] ?? '0'));

        if ($this->toCents($totalAmount) <= 0) {
            throw new DomainException(ui_text('expense.error.total_positive'));
        }

        $categoryId = $payload['category_id'] ?? null;

        if ($categoryId !== null && (string) $categoryId !== '') {
            $category = ($this->expenseCategoryModel ?? new ExpenseCategoryModel())
                ->findAvailableForHousehold($householdId, (int) $categoryId);

            if ($category === null) {
                throw new DomainException(ui_text('expense.error.category_invalid'));
            }
        }

        $expenseGroupId = null;

        if (($payload['expense_group_id'] ?? null) !== null && (string) $payload['expense_group_id'] !== '') {
            $expenseGroupId = (int) $payload['expense_group_id'];
            $group = ($this->expenseGroupModel ?? new ExpenseGroupModel())
                ->findForHousehold($householdId, $expenseGroupId);

            if ($group === null) {
                throw new DomainException(ui_text('expense.error.group_invalid'));
            }

            $uniqueMemberIds = ($this->expenseGroupMemberModel ?? new ExpenseGroupMemberModel())
                ->userIdsByGroupIds([$expenseGroupId])[$expenseGroupId] ?? [];
        }

        $payers = $this->normalizePayers((array) ($payload['payers'] ?? []), $uniqueMemberIds, $totalAmount);
        $participants = $this->normalizeParticipants((array) ($payload['splits'] ?? []), $uniqueMemberIds);
        $splitMethod = (string) ($payload['split_method'] ?? '');
        $calculator = $this->splitCalculationService ?? new SplitCalculationService();

        $splits = match ($splitMethod) {
            'equal' => $calculator->equal($totalAmount, array_map(
                static fn (array $participant): array => ['user_id' => (int) $participant['user_id']],
                $participants,
            )),
            'exact' => $calculator->exact($totalAmount, array_map(
                static fn (array $participant): array => [
                    'user_id' => (int) $participant['user_id'],
                    'owed_amount' => (string) $participant['owed_amount'],
                ],
                $participants,
            )),
            'percentage' => $calculator->percentage($totalAmount, array_map(
                static fn (array $participant): array => [
                    'user_id' => (int) $participant['user_id'],
                    'percentage' => (string) $participant['percentage'],
                ],
                $participants,
            )),
            'shares' => $calculator->shares($totalAmount, array_map(
                static fn (array $participant): array => [
                    'user_id' => (int) $participant['user_id'],
                    'share_units' => (string) $participant['share_units'],
                ],
                $participants,
            )),
            default => throw new DomainException(ui_text('expense.error.split_method_invalid')),
        };

        return [
            'payers' => $payers,
            'splits' => $splits,
            'participants' => $participants,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * @param array<string, mixed> $rawPayers
     * @param list<int> $memberIds
     * @return list<array{user_id:int,amount_paid:string}>
     */
    private function normalizePayers(array $rawPayers, array $memberIds, string $totalAmount): array
    {
        $payers = [];
        $sumCents = 0;

        foreach ($rawPayers as $userId => $payer) {
            if (! $this->isEnabled($payer['enabled'] ?? null)) {
                continue;
            }

            $resolvedUserId = (int) $userId;

            if (! in_array($resolvedUserId, $memberIds, true)) {
                throw new DomainException(ui_text('expense.error.member_payer_invalid'));
            }

            $amount = $this->normalizeMoney((string) ($payer['amount'] ?? '0'));
            $amountCents = $this->toCents($amount);

            if ($amountCents <= 0) {
                throw new DomainException(ui_text('expense.error.payer_positive'));
            }

            $sumCents += $amountCents;
            $payers[] = [
                'user_id' => $resolvedUserId,
                'amount_paid' => $amount,
            ];
        }

        if ($payers === []) {
            throw new DomainException(ui_text('expense.error.payers_required'));
        }

        if ($sumCents !== $this->toCents($totalAmount)) {
            throw new DomainException(ui_text('expense.error.payers_total'));
        }

        return $payers;
    }

    /**
     * @param array<string, mixed> $rawSplits
     * @param list<int> $memberIds
     * @return list<array<string, mixed>>
     */
    private function normalizeParticipants(array $rawSplits, array $memberIds): array
    {
        $participants = [];

        foreach ($rawSplits as $userId => $split) {
            if (! $this->isEnabled($split['enabled'] ?? null)) {
                continue;
            }

            $resolvedUserId = (int) $userId;

            if (! in_array($resolvedUserId, $memberIds, true)) {
                throw new DomainException(ui_text('expense.error.member_participant_invalid'));
            }

            $participants[] = [
                'user_id' => $resolvedUserId,
                'owed_amount' => $this->normalizeOptionalMoney((string) ($split['owed_amount'] ?? '0')),
                'percentage' => $this->normalizeDecimalString((string) ($split['percentage'] ?? '0')),
                'share_units' => $this->normalizeDecimalString((string) ($split['share_units'] ?? '0')),
            ];
        }

        if ($participants === []) {
            throw new DomainException(ui_text('expense.error.participants_required'));
        }

        return $participants;
    }

    private function normalizeMoney(string $value): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new DomainException(ui_text('expense.error.money_numeric'));
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeOptionalMoney(string $value): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '') {
            return '0.00';
        }

        return $this->normalizeMoney($normalized);
    }

    private function normalizeDecimalString(string $value): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            return '0.00';
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function isEnabled(mixed $value): bool
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

        return false;
    }
}
