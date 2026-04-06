<?php

declare(strict_types=1);

namespace App\Services\Expenses;

use DomainException;

final class SplitCalculationService
{
    /**
     * @param list<array{user_id:int}> $participants
     * @return list<array{user_id:int, owed_amount:string, percentage:null, share_units:null, is_excluded:int}>
     */
    public function equal(string $totalAmount, array $participants): array
    {
        helper('ui');

        if ($participants === []) {
            throw new DomainException(ui_text('expense.error.participants_required'));
        }

        $weights = [];

        foreach ($participants as $participant) {
            $weights[] = [
                'user_id' => (int) $participant['user_id'],
                'weight' => 1.0,
            ];
        }

        return $this->buildWeightedRows($this->toCents($totalAmount), $weights, 'equal');
    }

    /**
     * @param list<array{user_id:int, owed_amount:string}> $participants
     * @return list<array{user_id:int, owed_amount:string, percentage:null, share_units:null, is_excluded:int}>
     */
    public function exact(string $totalAmount, array $participants): array
    {
        helper('ui');

        if ($participants === []) {
            throw new DomainException(ui_text('expense.error.participants_required'));
        }

        $totalCents = $this->toCents($totalAmount);
        $sumCents = 0;
        $rows = [];

        foreach ($participants as $participant) {
            $owedCents = $this->toCents((string) $participant['owed_amount']);
            $sumCents += $owedCents;
            $rows[] = [
                'user_id' => (int) $participant['user_id'],
                'owed_amount' => $this->fromCents($owedCents),
                'percentage' => null,
                'share_units' => null,
                'is_excluded' => 0,
            ];
        }

        if ($sumCents !== $totalCents) {
            throw new DomainException(ui_text('expense.error.exact_total'));
        }

        return $rows;
    }

    /**
     * @param list<array{user_id:int, percentage:string}> $participants
     * @return list<array{user_id:int, owed_amount:string, percentage:string, share_units:null, is_excluded:int}>
     */
    public function percentage(string $totalAmount, array $participants): array
    {
        helper('ui');

        if ($participants === []) {
            throw new DomainException(ui_text('expense.error.participants_required'));
        }

        $weights = [];
        $percentageBasisPoints = 0;

        foreach ($participants as $participant) {
            $percentage = $this->normalizePercentage((string) $participant['percentage']);
            $weights[] = [
                'user_id' => (int) $participant['user_id'],
                'weight' => $percentage,
            ];
            $percentageBasisPoints += (int) round($percentage * 100);
        }

        if ($percentageBasisPoints !== 10000) {
            throw new DomainException(ui_text('expense.error.percentage_total'));
        }

        return $this->buildWeightedRows($this->toCents($totalAmount), $weights, 'percentage');
    }

    /**
     * @param list<array{user_id:int, share_units:string}> $participants
     * @return list<array{user_id:int, owed_amount:string, percentage:null, share_units:string, is_excluded:int}>
     */
    public function shares(string $totalAmount, array $participants): array
    {
        helper('ui');

        if ($participants === []) {
            throw new DomainException(ui_text('expense.error.participants_required'));
        }

        $weights = [];
        $totalShares = 0.0;

        foreach ($participants as $participant) {
            $shareUnits = $this->normalizeShareUnits((string) $participant['share_units']);
            $weights[] = [
                'user_id' => (int) $participant['user_id'],
                'weight' => $shareUnits,
            ];
            $totalShares += $shareUnits;
        }

        if ($totalShares <= 0) {
            throw new DomainException(ui_text('expense.error.shares_total'));
        }

        return $this->buildWeightedRows($this->toCents($totalAmount), $weights, 'shares');
    }

    /**
     * @param list<array{user_id:int, weight:float}> $weights
     * @return list<array{user_id:int, owed_amount:string, percentage:string|null, share_units:string|null, is_excluded:int}>
     */
    private function buildWeightedRows(int $totalCents, array $weights, string $method): array
    {
        $totalWeight = 0.0;

        foreach ($weights as $weight) {
            $totalWeight += $weight['weight'];
        }

        if ($totalWeight <= 0) {
            throw new DomainException(ui_text('expense.error.distribution_invalid'));
        }

        $allocations = [];
        $allocatedCents = 0;

        foreach ($weights as $index => $weight) {
            $raw = ($totalCents * $weight['weight']) / $totalWeight;
            $base = (int) floor($raw);
            $allocatedCents += $base;
            $allocations[] = [
                'index' => $index,
                'user_id' => $weight['user_id'],
                'base_cents' => $base,
                'fraction' => $raw - $base,
                'weight' => $weight['weight'],
            ];
        }

        $remainder = $totalCents - $allocatedCents;

        usort($allocations, static function (array $left, array $right): int {
            if ($left['fraction'] === $right['fraction']) {
                return $left['index'] <=> $right['index'];
            }

            return $left['fraction'] < $right['fraction'] ? 1 : -1;
        });

        for ($i = 0; $i < $remainder; $i++) {
            $allocations[$i]['base_cents']++;
        }

        usort($allocations, static fn (array $left, array $right): int => $left['index'] <=> $right['index']);

        return array_map(function (array $allocation) use ($method): array {
            return [
                'user_id' => (int) $allocation['user_id'],
                'owed_amount' => $this->fromCents((int) $allocation['base_cents']),
                'percentage' => $method === 'percentage' ? number_format((float) $allocation['weight'], 2, '.', '') : null,
                'share_units' => $method === 'shares' ? number_format((float) $allocation['weight'], 2, '.', '') : null,
                'is_excluded' => 0,
            ];
        }, $allocations);
    }

    private function toCents(string $amount): int
    {
        $normalized = $this->normalizeDecimal($amount);

        return (int) round($normalized * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function normalizeDecimal(string $value): float
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new DomainException(ui_text('expense.error.numeric_invalid'));
        }

        return round((float) $normalized, 6, PHP_ROUND_HALF_UP);
    }

    private function normalizePercentage(string $value): float
    {
        $percentage = $this->normalizeDecimal($value);

        if ($percentage <= 0) {
            throw new DomainException(ui_text('expense.error.percentage_positive'));
        }

        return round($percentage, 2, PHP_ROUND_HALF_UP);
    }

    private function normalizeShareUnits(string $value): float
    {
        $shareUnits = $this->normalizeDecimal($value);

        if ($shareUnits <= 0) {
            throw new DomainException(ui_text('expense.error.share_positive'));
        }

        return round($shareUnits, 2, PHP_ROUND_HALF_UP);
    }
}
