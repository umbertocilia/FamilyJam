<?php

declare(strict_types=1);

use App\Services\Balances\BalanceService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BalanceServiceTest extends CIUnitTestCase
{
    public function testExpenseEventBalancesReturnZeroSumLedger(): void
    {
        $service = new BalanceService();

        $balances = $service->expenseEventBalances(
            ['id' => 10, 'currency' => 'EUR'],
            [
                ['user_id' => 1, 'amount_paid' => '70.00'],
                ['user_id' => 2, 'amount_paid' => '30.00'],
            ],
            [
                ['user_id' => 1, 'owed_amount' => '25.00', 'is_excluded' => 0],
                ['user_id' => 2, 'owed_amount' => '25.00', 'is_excluded' => 0],
                ['user_id' => 3, 'owed_amount' => '25.00', 'is_excluded' => 0],
                ['user_id' => 4, 'owed_amount' => '25.00', 'is_excluded' => 0],
            ],
        );

        $this->assertSame(4500, $balances[1]);
        $this->assertSame(500, $balances[2]);
        $this->assertSame(-2500, $balances[3]);
        $this->assertSame(-2500, $balances[4]);
        $this->assertSame(0, array_sum($balances));
    }

    public function testPairwiseEdgesFromEventBalancesAreDeterministic(): void
    {
        $service = new BalanceService();

        $edges = $service->pairwiseEdgesFromEventBalances([
            1 => 600,
            2 => 300,
            3 => -500,
            4 => -400,
        ], 'EUR', 'expense', 5);

        $this->assertSame([
            ['from_user_id' => 3, 'to_user_id' => 1, 'amount' => '5.00'],
            ['from_user_id' => 4, 'to_user_id' => 1, 'amount' => '1.00'],
            ['from_user_id' => 4, 'to_user_id' => 2, 'amount' => '3.00'],
        ], array_map(static fn (array $row): array => [
            'from_user_id' => $row['from_user_id'],
            'to_user_id' => $row['to_user_id'],
            'amount' => $row['amount'],
        ], $edges));
    }

    public function testCompressPairwiseEdgesAccountsForSettlementReduction(): void
    {
        $service = new BalanceService();

        $compressed = $service->compressPairwiseEdges([
            ['currency' => 'EUR', 'from_user_id' => 3, 'to_user_id' => 1, 'amount_cents' => 2000, 'amount' => '20.00'],
            ['currency' => 'EUR', 'from_user_id' => 3, 'to_user_id' => 2, 'amount_cents' => 1500, 'amount' => '15.00'],
            ['currency' => 'EUR', 'from_user_id' => 1, 'to_user_id' => 3, 'amount_cents' => 1000, 'amount' => '10.00'],
        ], [
            1 => ['display_name' => 'Alice'],
            2 => ['display_name' => 'Bob'],
            3 => ['display_name' => 'Carla'],
        ]);

        $this->assertCount(2, $compressed['EUR']);
        $this->assertSame('10.00', $compressed['EUR'][0]['amount']);
        $this->assertSame('Carla', $compressed['EUR'][0]['from_user_name']);
        $this->assertSame('Alice', $compressed['EUR'][0]['to_user_name']);
        $this->assertSame('10.00', $compressed['EUR'][1]['amount']);
        $this->assertSame('Carla', $compressed['EUR'][1]['from_user_name']);
        $this->assertSame('Bob', $compressed['EUR'][1]['to_user_name']);
    }

    public function testAggregateNetBalanceMapSeparatesCurrencies(): void
    {
        $service = new BalanceService();

        $map = $service->aggregateNetBalanceMap([
            ['currency' => 'EUR', 'balances' => [1 => 1000, 2 => -1000]],
            ['currency' => 'USD', 'balances' => [1 => -500, 3 => 500]],
            ['currency' => 'EUR', 'balances' => [1 => -300, 3 => 300]],
        ]);

        $this->assertSame(700, $map['EUR'][1]);
        $this->assertSame(-1000, $map['EUR'][2]);
        $this->assertSame(300, $map['EUR'][3]);
        $this->assertSame(-500, $map['USD'][1]);
        $this->assertSame(500, $map['USD'][3]);
    }
}
