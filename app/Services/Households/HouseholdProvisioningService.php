<?php

declare(strict_types=1);

namespace App\Services\Households;

use App\Authorization\SystemRole;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\HouseholdModel;
use App\Models\Households\MembershipRoleModel;
use App\Models\Settings\HouseholdSettingModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class HouseholdProvisioningService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdModel $householdModel = null,
        private readonly ?HouseholdMembershipModel $membershipModel = null,
        private readonly ?MembershipRoleModel $membershipRoleModel = null,
        private readonly ?HouseholdSettingModel $householdSettingModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?RoleModel $roleModel = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @param array{name: string, description?: string|null, avatar_path?: string|null, base_currency?: string, timezone?: string, locale?: string, simplify_debts?: bool|int|string, chore_scoring_enabled?: bool|int|string} $payload
     * @return array<string, mixed>
     */
    public function create(int $ownerUserId, array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Household name is required.');
        }

        $db = $this->db ?? Database::connect();
        $householdModel = $this->householdModel ?? new HouseholdModel($db);
        $membershipModel = $this->membershipModel ?? new HouseholdMembershipModel($db);
        $membershipRoleModel = $this->membershipRoleModel ?? new MembershipRoleModel($db);
        $householdSettingModel = $this->householdSettingModel ?? new HouseholdSettingModel($db);
        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($db);
        $roleModel = $this->roleModel ?? new RoleModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $ownerRole = $roleModel->findByCode(SystemRole::OWNER);

        if ($ownerRole === null) {
            throw new RuntimeException('System roles not seeded. Run CoreAuthorizationSeeder before provisioning households.');
        }

        $now = Time::now()->toDateTimeString();
        $slug = $this->nextAvailableSlug($name, $householdModel);

        $db->transException(true)->transStart();

        try {
            $householdId = $householdModel->insert([
                'name' => $name,
                'slug' => $slug,
                'description' => $payload['description'] ?? null,
                'avatar_path' => $payload['avatar_path'] ?? null,
                'base_currency' => strtoupper((string) ($payload['base_currency'] ?? 'EUR')),
                'timezone' => (string) ($payload['timezone'] ?? 'Europe/Rome'),
                'simplify_debts' => $this->booleanFromPayload($payload, 'simplify_debts', true) ? 1 : 0,
                'chore_scoring_enabled' => $this->booleanFromPayload($payload, 'chore_scoring_enabled', true) ? 1 : 0,
                'is_archived' => 0,
                'created_by' => $ownerUserId,
            ], true);

            $membershipId = $membershipModel->insert([
                'household_id' => $householdId,
                'user_id' => $ownerUserId,
                'status' => 'active',
                'nickname' => null,
                'joined_at' => $now,
                'invited_by_user_id' => null,
            ], true);

            $membershipRoleModel->insert([
                'membership_id' => $membershipId,
                'role_id' => (int) $ownerRole['id'],
                'assigned_by_user_id' => $ownerUserId,
                'created_at' => $now,
            ]);

            $householdSettingModel->insert([
                'household_id' => $householdId,
                'locale' => (string) ($payload['locale'] ?? 'it'),
                'week_starts_on' => 1,
                'date_format' => 'd/m/Y',
                'time_format' => '24h',
                'notification_settings_json' => null,
                'module_settings_json' => null,
            ]);

            $preferences = $userPreferenceModel->where('user_id', $ownerUserId)->first();

            if ($preferences === null) {
                $userPreferenceModel->insert([
                    'user_id' => $ownerUserId,
                    'default_household_id' => $householdId,
                    'notification_preferences_json' => null,
                    'dashboard_preferences_json' => null,
                ]);
            } elseif (($preferences['default_household_id'] ?? null) === null) {
                $userPreferenceModel->update((int) $preferences['id'], [
                    'default_household_id' => $householdId,
                ]);
            }

            $auditLogService->record(
                action: 'household.created',
                entityType: 'household',
                entityId: (int) $householdId,
                actorUserId: $ownerUserId,
                householdId: (int) $householdId,
                metadata: ['slug' => $slug],
            );

            $db->transComplete();
        } catch (DatabaseException|Throwable $exception) {
            $db->transRollback();

            log_message('error', '[FamilyJam] household provisioning failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $household = $householdModel->find((int) $householdId);

        if ($household === null) {
            throw new RuntimeException('Failed to reload created household.');
        }

        return $household;
    }

    private function nextAvailableSlug(string $name, HouseholdModel $householdModel): string
    {
        $base = $this->slugify($name);
        $slug = $base;
        $sequence = 2;

        while ($householdModel->slugExists($slug)) {
            $slug = $base . '-' . $sequence;
            $sequence++;
        }

        return $slug;
    }

    private function slugify(string $name): string
    {
        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $name);
        } elseif (function_exists('iconv')) {
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        } else {
            $normalized = $name;
        }

        $normalized = strtolower((string) ($normalized ?: $name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'household';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function booleanFromPayload(array $payload, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $payload)) {
            return $default;
        }

        $value = $payload[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return ! empty($value);
    }
}
