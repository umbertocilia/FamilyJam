<?php

declare(strict_types=1);

use App\Services\Expenses\SplitCalculationService;
use CodeIgniter\Test\CIUnitTestCase;
use DomainException;

/**
 * @internal
 */
final class SplitCalculationServiceTest extends CIUnitTestCase
{
    public function testEqualSplitAllocatesDeterministicRemainder(): void
    {
        $service = new SplitCalculationService();

        $rows = $service->equal('10.00', [
            ['user_id' => 11],
            ['user_id' => 12],
            ['user_id' => 13],
        ]);

        $this->assertSame('3.34', $rows[0]['owed_amount']);
        $this->assertSame('3.33', $rows[1]['owed_amount']);
        $this->assertSame('3.33', $rows[2]['owed_amount']);
    }

    public function testPercentageSplitProducesRoundedOwingsAndStoresPercentages(): void
    {
        $service = new SplitCalculationService();

        $rows = $service->percentage('99.99', [
            ['user_id' => 21, 'percentage' => '50.00'],
            ['user_id' => 22, 'percentage' => '30.00'],
            ['user_id' => 23, 'percentage' => '20.00'],
        ]);

        $this->assertSame('50.00', $rows[0]['percentage']);
        $this->assertSame('50.00', $rows[0]['owed_amount']);
        $this->assertSame('30.00', $rows[1]['percentage']);
        $this->assertSame('30.00', $rows[1]['owed_amount']);
        $this->assertSame('20.00', $rows[2]['percentage']);
        $this->assertSame('19.99', $rows[2]['owed_amount']);
    }

    public function testSharesSplitRejectsZeroWeights(): void
    {
        $service = new SplitCalculationService();

        $this->expectException(DomainException::class);

        $service->shares('40.00', [
            ['user_id' => 31, 'share_units' => '0'],
            ['user_id' => 32, 'share_units' => '0'],
        ]);
    }

    public function testExactSplitRejectsMismatchedTotal(): void
    {
        $service = new SplitCalculationService();

        $this->expectException(DomainException::class);

        $service->exact('10.00', [
            ['user_id' => 1, 'owed_amount' => '4.00'],
            ['user_id' => 2, 'owed_amount' => '4.00'],
        ]);
    }

    public function testPercentageSplitRejectsPercentagesDifferentFromHundred(): void
    {
        $service = new SplitCalculationService();

        $this->expectException(DomainException::class);

        $service->percentage('20.00', [
            ['user_id' => 1, 'percentage' => '60.00'],
            ['user_id' => 2, 'percentage' => '30.00'],
        ]);
    }

    public function testSharesSplitAllocatesRemainderDeterministically(): void
    {
        $service = new SplitCalculationService();

        $rows = $service->shares('10.00', [
            ['user_id' => 1, 'share_units' => '1'],
            ['user_id' => 2, 'share_units' => '1'],
            ['user_id' => 3, 'share_units' => '1'],
        ]);

        $this->assertSame('3.34', $rows[0]['owed_amount']);
        $this->assertSame('3.33', $rows[1]['owed_amount']);
        $this->assertSame('3.33', $rows[2]['owed_amount']);
    }
}
