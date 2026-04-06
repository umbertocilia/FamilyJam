<?php

declare(strict_types=1);

namespace App\Services\Households;

use App\Models\Auth\UserPreferenceModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Services\Authorization\HouseholdAuthorizationService;
use CodeIgniter\Session\Session;

final class HouseholdContextService
{
    public function __construct(
        private readonly ?Session $session = null,
        private readonly ?HouseholdAuthorizationService $authorization = null,
        private readonly ?HouseholdMembershipModel $membershipModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
    ) {
    }

    public function setActiveHousehold(string $householdSlug): void
    {
        ($this->session ?? session())->set('app.active_household', $householdSlug);
    }

    public function clearActiveHousehold(): void
    {
        ($this->session ?? session())->remove('app.active_household');
    }

    public function activeHousehold(?string $householdSlug = null): ?array
    {
        $session = $this->session ?? session();
        $userId = $session->get('auth.user_id');
        $authorization = $this->authorization ?? service('householdAuthorization');

        if ($userId === null) {
            return null;
        }

        if (is_string($householdSlug) && $householdSlug !== '') {
            $membership = $authorization->membershipBySlug((int) $userId, $householdSlug);

            if ($membership !== null) {
                $this->setActiveHousehold($householdSlug);

                return $membership;
            }

            return null;
        }

        $activeSlug = $session->get('app.active_household');

        if (is_string($activeSlug) && $activeSlug !== '') {
            $membership = $authorization->membershipBySlug((int) $userId, $activeSlug);

            if ($membership !== null) {
                return $membership;
            }

            $this->clearActiveHousehold();
        }

        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel();
        $preferences = $userPreferenceModel->where('user_id', (int) $userId)->first();

        if ($preferences !== null && ! empty($preferences['default_household_id'])) {
            $membership = $authorization->membership((int) $userId, (int) $preferences['default_household_id']);

            if ($membership !== null) {
                $this->setActiveHousehold((string) $membership['household_slug']);

                return $membership;
            }
        }

        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel();
        $memberships = $membershipModel->findActiveMembershipsForUser((int) $userId);

        if (count($memberships) === 1) {
            $this->setActiveHousehold((string) $memberships[0]['household_slug']);

            return $memberships[0];
        }

        return null;
    }

    public function activeHouseholdByIdentifier(?string $identifier = null): ?array
    {
        $session = $this->session ?? session();
        $userId = $session->get('auth.user_id');
        $authorization = $this->authorization ?? service('householdAuthorization');

        if ($userId === null) {
            return null;
        }

        if (is_string($identifier) && $identifier !== '') {
            $membership = $authorization->membershipByIdentifier((int) $userId, $identifier);

            if ($membership !== null) {
                $this->setActiveHousehold((string) $membership['household_slug']);

                return $membership;
            }

            return null;
        }

        return $this->activeHousehold();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function availableHouseholds(?int $userId = null): array
    {
        $resolvedUserId = $userId ?? (($this->session ?? session())->get('auth.user_id'));

        if ($resolvedUserId === null) {
            return [];
        }

        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel();

        return $membershipModel->findActiveMembershipsForUser((int) $resolvedUserId);
    }
}
