<?php

declare(strict_types=1);

namespace App\Models\Households;

use App\Models\TenantScopedModel;

final class HouseholdMembershipModel extends TenantScopedModel
{
    protected $table = 'household_memberships';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'household_id',
        'user_id',
        'invited_by_user_id',
        'status',
        'nickname',
        'joined_at',
        'deleted_at',
    ];

    private const MEMBERSHIP_COLUMNS = 'household_memberships.id, household_memberships.household_id, household_memberships.user_id, household_memberships.invited_by_user_id, household_memberships.status, household_memberships.nickname, household_memberships.joined_at, household_memberships.created_at, household_memberships.updated_at, household_memberships.deleted_at';
    private const HOUSEHOLD_COLUMNS = 'households.slug AS household_slug, households.name AS household_name';
    private const USER_COLUMNS = 'users.email, users.display_name, users.first_name, users.last_name, users.avatar_path';

    public function findActiveMembership(int $householdId, int $userId): ?array
    {
        $membership = $this->select(self::MEMBERSHIP_COLUMNS . ', ' . self::HOUSEHOLD_COLUMNS)
            ->join('households', 'households.id = household_memberships.household_id', 'inner')
            ->where('household_memberships.household_id', $householdId)
            ->where('household_memberships.user_id', $userId)
            ->where('household_memberships.status', 'active')
            ->where('households.deleted_at', null)
            ->first();

        return $membership === null ? null : $this->hydrateRoleSummary($membership);
    }

    public function findActiveMembershipBySlug(string $householdSlug, int $userId): ?array
    {
        $membership = $this->select(self::MEMBERSHIP_COLUMNS . ', ' . self::HOUSEHOLD_COLUMNS)
            ->join('households', 'households.id = household_memberships.household_id', 'inner')
            ->where('households.slug', $householdSlug)
            ->where('household_memberships.user_id', $userId)
            ->where('household_memberships.status', 'active')
            ->where('households.deleted_at', null)
            ->first();

        return $membership === null ? null : $this->hydrateRoleSummary($membership);
    }

    public function findActiveMembershipByIdentifier(string $identifier, int $userId): ?array
    {
        if (ctype_digit($identifier)) {
            return $this->findActiveMembership((int) $identifier, $userId);
        }

        return $this->findActiveMembershipBySlug($identifier, $userId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findActiveMembershipsForUser(int $userId): array
    {
        $memberships = $this->select(self::MEMBERSHIP_COLUMNS . ', ' . self::HOUSEHOLD_COLUMNS)
            ->join('households', 'households.id = household_memberships.household_id', 'inner')
            ->where('household_memberships.user_id', $userId)
            ->where('household_memberships.status', 'active')
            ->where('households.deleted_at', null)
            ->orderBy('households.name', 'ASC')
            ->findAll();

        return $this->hydrateRoleSummaries($memberships);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForHousehold(int $householdId): array
    {
        $memberships = $this->select(self::MEMBERSHIP_COLUMNS . ', ' . self::USER_COLUMNS)
            ->join('users', 'users.id = household_memberships.user_id', 'inner')
            ->where('household_memberships.household_id', $householdId)
            ->where('users.deleted_at', null)
            ->orderBy('household_memberships.status', 'ASC')
            ->orderBy('users.display_name', 'ASC')
            ->findAll();

        return $this->hydrateRoleSummaries($memberships);
    }

    public function findMembershipDetail(int $householdId, int $membershipId): ?array
    {
        $membership = $this->select(self::MEMBERSHIP_COLUMNS . ', ' . self::USER_COLUMNS)
            ->join('users', 'users.id = household_memberships.user_id', 'inner')
            ->where('household_memberships.household_id', $householdId)
            ->where('household_memberships.id', $membershipId)
            ->first();

        return $membership === null ? null : $this->hydrateRoleSummary($membership);
    }

    public function findAnyMembership(int $householdId, int $userId): ?array
    {
        return $this->withDeleted()
            ->where('household_id', $householdId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActiveMembersForAssignment(int $householdId): array
    {
        return $this->select('household_memberships.*, users.email, users.display_name, users.first_name, users.last_name, users.avatar_path')
            ->join('users', 'users.id = household_memberships.user_id', 'inner')
            ->where('household_memberships.household_id', $householdId)
            ->where('household_memberships.status', 'active')
            ->where('household_memberships.deleted_at', null)
            ->where('users.deleted_at', null)
            ->orderBy('household_memberships.joined_at', 'ASC')
            ->orderBy('household_memberships.user_id', 'ASC')
            ->findAll();
    }

    /**
     * @param array<string, mixed> $membership
     * @return array<string, mixed>
     */
    private function hydrateRoleSummary(array $membership): array
    {
        $roles = $this->fetchRoleSummaryMap([(int) $membership['id']]);
        $summary = $roles[(int) $membership['id']] ?? ['role_codes' => '', 'role_names' => ''];

        return array_merge($membership, $summary);
    }

    /**
     * @param list<array<string, mixed>> $memberships
     * @return list<array<string, mixed>>
     */
    private function hydrateRoleSummaries(array $memberships): array
    {
        if ($memberships === []) {
            return [];
        }

        $membershipIds = array_map(static fn (array $row): int => (int) $row['id'], $memberships);
        $summaryMap = $this->fetchRoleSummaryMap($membershipIds);

        foreach ($memberships as &$membership) {
            $summary = $summaryMap[(int) $membership['id']] ?? ['role_codes' => '', 'role_names' => ''];
            $membership = array_merge($membership, $summary);
        }

        unset($membership);

        return $memberships;
    }

    /**
     * @param list<int> $membershipIds
     * @return array<int, array{role_codes: string, role_names: string}>
     */
    private function fetchRoleSummaryMap(array $membershipIds): array
    {
        if ($membershipIds === []) {
            return [];
        }

        $rows = $this->db->table('membership_roles')
            ->select([
                'membership_roles.membership_id',
                'roles.code',
                'roles.name',
            ])
            ->join('roles', 'roles.id = membership_roles.role_id', 'inner')
            ->whereIn('membership_roles.membership_id', $membershipIds)
            ->where('roles.deleted_at', null)
            ->get()
            ->getResultArray();

        $summary = [];

        foreach ($rows as $row) {
            $membershipId = (int) $row['membership_id'];

            if (! isset($summary[$membershipId])) {
                $summary[$membershipId] = [
                    'role_codes' => [],
                    'role_names' => [],
                ];
            }

            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code !== '' && ! in_array($code, $summary[$membershipId]['role_codes'], true)) {
                $summary[$membershipId]['role_codes'][] = $code;
            }

            if ($name !== '' && ! in_array($name, $summary[$membershipId]['role_names'], true)) {
                $summary[$membershipId]['role_names'][] = $name;
            }
        }

        foreach ($membershipIds as $membershipId) {
            if (! isset($summary[$membershipId])) {
                $summary[$membershipId] = [
                    'role_codes' => [],
                    'role_names' => [],
                ];
            }
        }

        return array_map(
            static fn (array $row): array => [
                'role_codes' => implode(',', $row['role_codes']),
                'role_names' => implode(', ', $row['role_names']),
            ],
            $summary,
        );
    }
}
