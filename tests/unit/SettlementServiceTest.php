<?php

declare(strict_types=1);

use App\Authorization\Permission;
use App\Models\Households\HouseholdMembershipModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Balances\SettlementService;
use CodeIgniter\Test\CIUnitTestCase;
use DomainException;

/**
 * @internal
 */
final class SettlementServiceTest extends CIUnitTestCase
{
    public function testCreateRejectsSettlementBetweenSameUser(): void
    {
        $service = new SettlementService(
            householdAuthorizationService: new class extends HouseholdAuthorizationService {
                public function membershipByIdentifier(int $userId, string $identifier): ?array
                {
                    return $userId === 7 && $identifier === 'tenant-alpha'
                        ? ['id' => 11, 'household_id' => 99, 'user_id' => 7]
                        : null;
                }

                public function hasPermission(int $userId, string $identifier, string $permission): bool
                {
                    return $userId === 7 && $identifier === 'tenant-alpha' && $permission === Permission::ADD_SETTLEMENT;
                }
            },
            householdMembershipModel: new class extends HouseholdMembershipModel {
                public function listForHousehold(int $householdId): array
                {
                    return $householdId === 99
                        ? [
                            ['user_id' => 7],
                            ['user_id' => 8],
                        ]
                        : [];
                }
            },
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('due utenti distinti');

        $service->create(7, 'tenant-alpha', [
            'from_user_id' => '7',
            'to_user_id' => '7',
            'settlement_date' => '2026-04-03',
            'currency' => 'EUR',
            'amount' => '10.00',
        ]);
    }

    public function testCreateRejectsNonPositiveSettlementAmount(): void
    {
        $service = new SettlementService(
            householdAuthorizationService: new class extends HouseholdAuthorizationService {
                public function membershipByIdentifier(int $userId, string $identifier): ?array
                {
                    return $userId === 7 && $identifier === 'tenant-alpha'
                        ? ['id' => 11, 'household_id' => 99, 'user_id' => 7]
                        : null;
                }

                public function hasPermission(int $userId, string $identifier, string $permission): bool
                {
                    return $userId === 7 && $identifier === 'tenant-alpha' && $permission === Permission::ADD_SETTLEMENT;
                }
            },
            householdMembershipModel: new class extends HouseholdMembershipModel {
                public function listForHousehold(int $householdId): array
                {
                    return $householdId === 99
                        ? [
                            ['user_id' => 7],
                            ['user_id' => 8],
                        ]
                        : [];
                }
            },
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('maggiore di zero');

        $service->create(7, 'tenant-alpha', [
            'from_user_id' => '7',
            'to_user_id' => '8',
            'settlement_date' => '2026-04-03',
            'currency' => 'EUR',
            'amount' => '0.00',
        ]);
    }
}
