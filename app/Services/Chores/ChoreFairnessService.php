<?php

declare(strict_types=1);

namespace App\Services\Chores;

use App\Models\Chores\ChoreOccurrenceModel;
use App\Models\Households\HouseholdModel;
use App\Services\Authorization\HouseholdAuthorizationService;

final class ChoreFairnessService
{
    public function __construct(
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?ChoreOccurrenceModel $choreOccurrenceModel = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function dashboardContext(int $userId, string $identifier): ?array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return null;
        }

        $household = ($this->householdModel ?? new HouseholdModel())->find((int) $membership['household_id']);

        if ($household === null) {
            return null;
        }

        $rows = ($this->choreOccurrenceModel ?? new ChoreOccurrenceModel())
            ->fairnessRows((int) $household['id']);

        return [
            'membership' => $membership,
            'household' => $household,
            'rows' => $rows,
            'totals' => [
                'points' => array_sum(array_map(static fn (array $row): int => (int) $row['points_total'], $rows)),
                'completed' => array_sum(array_map(static fn (array $row): int => (int) $row['completed_count'], $rows)),
                'overdue' => array_sum(array_map(static fn (array $row): int => (int) $row['overdue_count'], $rows)),
            ],
        ];
    }
}
