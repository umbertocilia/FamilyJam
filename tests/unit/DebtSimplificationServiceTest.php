<?php

declare(strict_types=1);

use App\Services\Balances\DebtSimplificationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DebtSimplificationServiceTest extends CIUnitTestCase
{
    public function testSimplifyReducesTransfersFromNetBalances(): void
    {
        $service = new DebtSimplificationService();

        $result = $service->simplify([
            'EUR' => [
                1 => 4000,
                2 => -500,
                3 => -3500,
            ],
        ], [
            1 => ['display_name' => 'Alice'],
            2 => ['display_name' => 'Bob'],
            3 => ['display_name' => 'Carla'],
        ]);

        $this->assertCount(2, $result['EUR']);
        $this->assertSame('Carla', $result['EUR'][0]['from_user_name']);
        $this->assertSame('Alice', $result['EUR'][0]['to_user_name']);
        $this->assertSame('35.00', $result['EUR'][0]['amount']);
        $this->assertSame('Bob', $result['EUR'][1]['from_user_name']);
        $this->assertSame('5.00', $result['EUR'][1]['amount']);
    }

    public function testSimplifyKeepsCurrenciesSeparated(): void
    {
        $service = new DebtSimplificationService();

        $result = $service->simplify([
            'EUR' => [1 => 1000, 2 => -1000],
            'USD' => [1 => -500, 2 => 500],
        ]);

        $this->assertCount(1, $result['EUR']);
        $this->assertCount(1, $result['USD']);
        $this->assertSame('10.00', $result['EUR'][0]['amount']);
        $this->assertSame('5.00', $result['USD'][0]['amount']);
    }
}
