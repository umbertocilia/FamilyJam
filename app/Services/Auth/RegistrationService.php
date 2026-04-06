<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DomainException;

final class RegistrationService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?AuthTokenService $authTokenService = null,
        private readonly ?OutboundEmailService $outboundEmailService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function register(array $payload): array
    {
        $db = $this->db ?? Database::connect();
        $userModel = $this->userModel ?? new UserModel($db);
        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($db);
        $authTokenService = $this->authTokenService ?? new AuthTokenService(new AuthTokenModel($db));
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $outboundEmailService = $this->outboundEmailService ?? service('outboundEmail');

        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($userModel->findByEmail($email, true) !== null) {
            throw new DomainException('Esiste gia un account registrato con questa email.');
        }

        $displayName = trim((string) ($payload['display_name'] ?? ''));
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));

        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        if ($displayName === '') {
            $displayName = strstr($email, '@', true) ?: $email;
        }

        $emailNotifications = $this->boolish($payload['email_notifications'] ?? true);

        $db->transException(true)->transStart();

        $userId = $userModel->insert([
            'email' => $email,
            'password_hash' => password_hash((string) $payload['password'], PASSWORD_DEFAULT),
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'display_name' => $displayName,
            'avatar_path' => $this->nullableString($payload['avatar_path'] ?? null),
            'locale' => (string) ($payload['locale'] ?? 'it'),
            'theme' => (string) ($payload['theme'] ?? 'system'),
            'timezone' => (string) ($payload['timezone'] ?? 'Europe/Rome'),
            'status' => 'active',
        ], true);

        $userPreferenceModel->insert([
            'user_id' => $userId,
            'default_household_id' => null,
            'notification_preferences_json' => json_encode([
                'email_notifications' => $emailNotifications,
            ], JSON_THROW_ON_ERROR),
            'dashboard_preferences_json' => null,
        ]);

        $token = $authTokenService->issue(
            (int) $userId,
            AuthTokenType::EMAIL_VERIFICATION,
            date('Y-m-d H:i:s', strtotime('+2 days')),
        );

        $auditLogService->record(
            action: 'auth.registered',
            entityType: 'user',
            entityId: (int) $userId,
            actorUserId: (int) $userId,
            metadata: ['email' => $email],
        );

        $db->transComplete();

        $user = $userModel->find((int) $userId);

        if ($user !== null) {
            $outboundEmailService->sendEmailVerification($user, $token['token']);
        }

        return $user ?? [];
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
