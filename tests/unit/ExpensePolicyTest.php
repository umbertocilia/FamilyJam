<?php

declare(strict_types=1);

use App\Authorization\Permission;
use App\Authorization\Policies\ExpensePolicy;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ExpensePolicyTest extends CIUnitTestCase
{
    public function testUpdateAllowsAnyExpenseWhenPermissionIsGranted(): void
    {
        $policy = new ExpensePolicy();

        $this->assertTrue($policy->update([
            'membership' => ['user_id' => 5],
            'permissions' => [Permission::EDIT_ANY_EXPENSE],
            'role_codes' => ['admin'],
        ], [
            'created_by' => 99,
        ]));
    }

    public function testUpdateAllowsOwnExpenseWhenMembershipOwnsResource(): void
    {
        $policy = new ExpensePolicy();

        $this->assertTrue($policy->update([
            'membership' => ['user_id' => 7],
            'permissions' => [Permission::EDIT_OWN_EXPENSE],
            'role_codes' => ['member'],
        ], [
            'created_by' => 7,
        ]));
    }

    public function testUpdateDeniesWhenOnlyOwnPermissionAndResourceBelongsToAnotherUser(): void
    {
        $policy = new ExpensePolicy();

        $this->assertFalse($policy->update([
            'membership' => ['user_id' => 7],
            'permissions' => [Permission::EDIT_OWN_EXPENSE],
            'role_codes' => ['member'],
        ], [
            'created_by' => 8,
        ]));
    }
}
