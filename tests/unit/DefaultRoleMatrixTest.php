<?php

declare(strict_types=1);

use App\Authorization\DefaultRoleMatrix;
use App\Authorization\Permission;
use App\Authorization\SystemRole;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DefaultRoleMatrixTest extends CIUnitTestCase
{
    public function testOwnerReceivesEveryPermission(): void
    {
        $this->assertSame(Permission::all(), DefaultRoleMatrix::permissionsFor(SystemRole::OWNER));
    }

    public function testAdminCannotManageHouseholdLifecycle(): void
    {
        $this->assertNotContains(
            Permission::MANAGE_HOUSEHOLD,
            DefaultRoleMatrix::permissionsFor(SystemRole::ADMIN),
        );
    }

    public function testGuestKeepsLimitedOperationalAccess(): void
    {
        $permissions = DefaultRoleMatrix::permissionsFor(SystemRole::GUEST);

        $this->assertContains(Permission::COMPLETE_CHORE, $permissions);
        $this->assertContains(Permission::MANAGE_SHOPPING, $permissions);
        $this->assertNotContains(Permission::CREATE_EXPENSE, $permissions);
    }
}
