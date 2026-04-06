<?php

declare(strict_types=1);

use App\Authorization\Permission;
use App\Models\Authorization\RolePermissionModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\MembershipRoleModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HouseholdAuthorizationServiceTest extends CIUnitTestCase
{
    public function testHasPermissionHasRoleAndCanManageResolveMembershipContext(): void
    {
        $membership = [
            'id' => 10,
            'household_id' => 20,
            'user_id' => 30,
            'role_codes' => 'member,finance_editor',
        ];

        $service = new HouseholdAuthorizationService(
            membershipModel: new class($membership) extends HouseholdMembershipModel {
                public function __construct(private readonly array $membership)
                {
                }

                public function findActiveMembershipByIdentifier($identifier, $userId): ?array
                {
                    if ((string) $identifier !== 'tenant-alpha' || (int) $userId !== 30) {
                        return null;
                    }

                    return $this->membership;
                }
            },
            membershipRoleModel: new class extends MembershipRoleModel {
                public function findRoleIdsByMembershipId(int $membershipId): array
                {
                    return $membershipId === 10 ? [1, 2] : [];
                }

                public function findRoleCodesByMembershipId(int $membershipId): array
                {
                    return $membershipId === 10 ? ['member', 'finance_editor'] : [];
                }
            },
            rolePermissionModel: new class extends RolePermissionModel {
                public function findPermissionCodesByRoleIds(array $roleIds): array
                {
                    sort($roleIds);

                    if ($roleIds !== [1, 2]) {
                        return [];
                    }

                    return [
                        Permission::CREATE_EXPENSE,
                        Permission::EDIT_OWN_EXPENSE,
                        Permission::VIEW_REPORTS,
                    ];
                }
            },
        );

        $this->assertTrue($service->hasPermission(30, 'tenant-alpha', Permission::CREATE_EXPENSE));
        $this->assertTrue($service->hasRole(30, 'tenant-alpha', 'finance_editor'));
        $this->assertTrue($service->canManage(30, 'tenant-alpha', 'edit_expense', ['created_by' => 30]));
        $this->assertFalse($service->canManage(30, 'tenant-alpha', 'edit_expense', ['created_by' => 99]));
        $this->assertFalse($service->hasPermission(31, 'tenant-alpha', Permission::CREATE_EXPENSE));
    }
}
