<?php

declare(strict_types=1);

namespace App\Services\Balances;

final class DebtSimplificationService
{
    /**
     * @param array<string, array<int, int>> $netBalanceMap
     * @param array<int, array<string, mixed>> $memberDirectory
     * @return array<string, list<array<string, mixed>>>
     */
    public function simplify(array $netBalanceMap, array $memberDirectory = []): array
    {
        $result = [];

        foreach ($netBalanceMap as $currency => $balances) {
            $creditors = [];
            $debtors = [];

            foreach ($balances as $userId => $cents) {
                if ($cents > 0) {
                    $creditors[] = ['user_id' => (int) $userId, 'remaining_cents' => $cents];
                } elseif ($cents < 0) {
                    $debtors[] = ['user_id' => (int) $userId, 'remaining_cents' => abs($cents)];
                }
            }

            usort($creditors, static fn (array $left, array $right): int => $right['remaining_cents'] <=> $left['remaining_cents'] ?: $left['user_id'] <=> $right['user_id']);
            usort($debtors, static fn (array $left, array $right): int => $right['remaining_cents'] <=> $left['remaining_cents'] ?: $left['user_id'] <=> $right['user_id']);

            $currencyTransfers = [];
            $creditorIndex = 0;
            $debtorIndex = 0;

            while (isset($debtors[$debtorIndex], $creditors[$creditorIndex])) {
                $transferCents = min($debtors[$debtorIndex]['remaining_cents'], $creditors[$creditorIndex]['remaining_cents']);

                $currencyTransfers[] = [
                    'currency' => $currency,
                    'from_user_id' => $debtors[$debtorIndex]['user_id'],
                    'to_user_id' => $creditors[$creditorIndex]['user_id'],
                    'amount' => $this->fromCents($transferCents),
                    'amount_cents' => $transferCents,
                    'from_user_name' => $memberDirectory[$debtors[$debtorIndex]['user_id']]['display_name'] ?? $memberDirectory[$debtors[$debtorIndex]['user_id']]['email'] ?? 'User #' . $debtors[$debtorIndex]['user_id'],
                    'to_user_name' => $memberDirectory[$creditors[$creditorIndex]['user_id']]['display_name'] ?? $memberDirectory[$creditors[$creditorIndex]['user_id']]['email'] ?? 'User #' . $creditors[$creditorIndex]['user_id'],
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

            $result[$currency] = $currencyTransfers;
        }

        return $result;
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
