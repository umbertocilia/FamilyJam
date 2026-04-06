<?php

declare(strict_types=1);

namespace App\Controllers\Web\Balances;

use App\Controllers\BaseController;
use CodeIgniter\Security\Exceptions\SecurityException;

final class BalanceController extends BaseController
{
    public function overview(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('balanceService')->overviewContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('balances/overview', [
            'pageClass' => 'balances-page',
            'pageTitle' => 'Balances | FamilyJam',
            'balanceContext' => $context,
        ]);
    }

    public function personal(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('balanceService')->personalContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('balances/personal', [
            'pageClass' => 'balances-page',
            'pageTitle' => 'Personal Balance | FamilyJam',
            'balanceContext' => $context,
        ]);
    }

    public function pairwise(string $identifier): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $context = service('balanceService')->pairwiseContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('balances/pairwise', [
            'pageClass' => 'balances-page',
            'pageTitle' => 'Who Owes Whom | FamilyJam',
            'balanceContext' => $context,
        ]);
    }
}
