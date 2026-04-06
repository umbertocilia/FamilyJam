<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Services\Audit\AuditLogService;
use App\Services\Media\AvatarImageService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;

final class UserProfileService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?AvatarImageService $avatarImageService = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(int $userId): ?array
    {
        $user = ($this->userModel ?? new UserModel())->find($userId);

        if ($user === null) {
            return null;
        }

        $preferences = ($this->userPreferenceModel ?? new UserPreferenceModel())->findByUserId($userId);
        $notificationPreferences = [];

        if (! empty($preferences['notification_preferences_json'])) {
            $notificationPreferences = (array) json_decode((string) $preferences['notification_preferences_json'], true, 512, JSON_THROW_ON_ERROR);
        }

        $user['email_notifications'] = (bool) ($notificationPreferences['email_notifications'] ?? true);
        $user['default_household_id'] = $preferences['default_household_id'] ?? null;

        return $user;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function update(int $userId, array $payload, ?UploadedFile $avatarImage = null): ?array
    {
        $db = $this->db ?? Database::connect();
        $userModel = $this->userModel ?? new UserModel($db);
        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $avatarImageService = $this->avatarImageService ?? service('avatarImages');
        $current = $this->profile($userId);

        if ($current === null) {
            return null;
        }

        $displayName = trim((string) ($payload['display_name'] ?? ''));

        if ($displayName === '') {
            $displayName = trim((string) ($payload['first_name'] ?? '') . ' ' . (string) ($payload['last_name'] ?? ''));
        }

        $db->transException(true)->transStart();

        $avatarPath = $avatarImageService->storeUserAvatar($avatarImage, $userId, isset($current['avatar_path']) ? (string) $current['avatar_path'] : null);

        $userModel->update($userId, [
            'first_name' => $this->nullableString($payload['first_name'] ?? null),
            'last_name' => $this->nullableString($payload['last_name'] ?? null),
            'display_name' => $displayName !== '' ? $displayName : (string) $current['display_name'],
            'avatar_path' => $this->nullableString($avatarPath),
            'locale' => (string) ($payload['locale'] ?? $current['locale']),
            'theme' => (string) ($payload['theme'] ?? $current['theme']),
            'timezone' => (string) ($payload['timezone'] ?? $current['timezone']),
        ]);

        $preferences = $userPreferenceModel->findByUserId($userId);
        $notificationPreferences = [
            'email_notifications' => $this->boolish($payload['email_notifications'] ?? $current['email_notifications']),
        ];

        if ($preferences === null) {
            $userPreferenceModel->insert([
                'user_id' => $userId,
                'default_household_id' => null,
                'notification_preferences_json' => json_encode($notificationPreferences, JSON_THROW_ON_ERROR),
                'dashboard_preferences_json' => null,
            ]);
        } else {
            $userPreferenceModel->update((int) $preferences['id'], [
                'notification_preferences_json' => json_encode($notificationPreferences, JSON_THROW_ON_ERROR),
            ]);
        }

        $auditLogService->record(
            action: 'user.profile_updated',
            entityType: 'user',
            entityId: $userId,
            actorUserId: $userId,
        );

        $db->transComplete();

        session()->set('app.locale', (string) ($payload['locale'] ?? $current['locale']));
        session()->set('app.theme', (string) ($payload['theme'] ?? $current['theme'] ?? 'system'));

        return $this->profile($userId);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function boolish(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return ! empty($value);
    }
}
