<?php

declare(strict_types=1);

use App\Services\Chores\ChoreRotationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ChoreRotationServiceTest extends CIUnitTestCase
{
    public function testAnchorIsUsedForFirstAssignment(): void
    {
        $service = new ChoreRotationService();
        $members = [
            ['user_id' => 10],
            ['user_id' => 20],
            ['user_id' => 30],
        ];

        $this->assertSame(20, $service->nextAssigneeUserId($members, 20, null));
    }

    public function testRotationAdvancesAfterLastAssignedMember(): void
    {
        $service = new ChoreRotationService();
        $members = [
            ['user_id' => 10],
            ['user_id' => 20],
            ['user_id' => 30],
        ];

        $this->assertSame(30, $service->nextAssigneeUserId($members, 20, 20));
        $this->assertSame(10, $service->nextAssigneeUserId($members, 20, 30));
    }

    public function testInactiveLastAssignedFallsBackToNextValidCyclePoint(): void
    {
        $service = new ChoreRotationService();
        $members = [
            ['user_id' => 10],
            ['user_id' => 20],
        ];

        $this->assertSame(20, $service->nextAssigneeUserId($members, 20, 30));
    }
}
