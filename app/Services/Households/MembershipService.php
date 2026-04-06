<?php

declare(strict_types=1);

namespace App\Services\Households;

use App\Models\Households\HouseholdMembershipModel;
use App\Services\Authorization\HouseholdAuthorizationService;

final class MembershipService
{
    public function __construct(
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
    ) {
    }

    /**
     * @return array{membership: array<string, mixed>, members: list<array<string, mixed>>}|null
     */
    public function listForHousehold(int $userId, string $identifier): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $members = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->listForHousehold((int) $membership['household_id']);

        return [
            'membership' => $membership,
            'members' => $members,
        ];
    }

    /**
     * @return array{membership: array<string, mixed>, detail: array<string, mixed>}|null
     */
    public function detailForHousehold(int $userId, string $identifier, int $membershipId): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $detail = ($this->householdMembershipModel ?? new HouseholdMembershipModel())
            ->findMembershipDetail((int) $membership['household_id'], $membershipId);

        if ($detail === null) {
            return null;
        }

        return [
            'membership' => $membership,
            'detail' => $detail,
        ];
    }
}
