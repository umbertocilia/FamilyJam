<?php

declare(strict_types=1);

namespace App\Authorization\Policies;

use App\Authorization\Permission;

final class ExpensePolicy extends BasePolicy
{
    /**
     * @param array{membership: array<string, mixed>, permissions: list<string>, role_codes: list<string>} $context
     * @param array<string, mixed>|null $expense
     */
    public function update(array $context, ?array $expense): bool
    {
        if ($this->hasPermission($context, Permission::EDIT_ANY_EXPENSE)) {
            return true;
        }

        if ($expense === null || ! $this->hasPermission($context, Permission::EDIT_OWN_EXPENSE)) {
            return false;
        }

        return (int) ($expense['created_by'] ?? 0) === (int) ($context['membership']['user_id'] ?? 0);
    }
}
