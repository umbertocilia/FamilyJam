<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Models\Legal\PrivacyConsentModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Config\Database;

final class PrivacyConsentService
{
    public const POLICY_VERSION = '2026.04';
    public const CONSENT_ID_COOKIE = 'familyjam_consent_id';
    public const CONSENT_STATE_COOKIE = 'familyjam_cookie_consent';
    private const COOKIE_TTL = 15_552_000;

    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?PrivacyConsentModel $privacyConsentModel = null,
        private readonly ?IncomingRequest $request = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function viewContext(?int $userId = null): array
    {
        $state = $this->currentState($userId);

        return [
            'policyVersion' => self::POLICY_VERSION,
            'state' => $state,
            'bannerRequired' => ! (bool) $state['has_choice'],
            'preferencesPersistAllowed' => (bool) $state['preferences'],
            'links' => [
                'privacy' => route_url('legal.privacy'),
                'cookies' => route_url('legal.cookies'),
                'essential' => route_url('legal.cookies.essential'),
                'acceptAll' => route_url('legal.cookies.accept_all'),
                'save' => route_url('legal.cookies.save'),
            ],
            'categories' => [
                'necessary' => ['enabled' => true],
                'preferences' => ['enabled' => (bool) $state['preferences']],
                'analytics' => ['enabled' => (bool) $state['analytics']],
                'marketing' => ['enabled' => (bool) $state['marketing']],
            ],
        ];
    }

    /**
     * @return array{consent_uuid:string, state: array<string, mixed>}
     */
    public function acceptEssential(?int $userId, string $locale, string $source = 'banner'): array
    {
        return $this->recordConsent($userId, $locale, [
            'necessary' => true,
            'preferences' => false,
            'analytics' => false,
            'marketing' => false,
        ], $source);
    }

    /**
     * @return array{consent_uuid:string, state: array<string, mixed>}
     */
    public function acceptAll(?int $userId, string $locale, string $source = 'banner'): array
    {
        return $this->recordConsent($userId, $locale, [
            'necessary' => true,
            'preferences' => true,
            'analytics' => true,
            'marketing' => false,
        ], $source);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{consent_uuid:string, state: array<string, mixed>}
     */
    public function savePreferences(?int $userId, string $locale, array $payload, string $source = 'preferences'): array
    {
        return $this->recordConsent($userId, $locale, [
            'necessary' => true,
            'preferences' => $this->boolish($payload['preferences'] ?? false),
            'analytics' => $this->boolish($payload['analytics'] ?? false),
            'marketing' => $this->boolish($payload['marketing'] ?? false),
        ], $source);
    }

    public function applyCookies(ResponseInterface $response, string $consentUuid, array $state): ResponseInterface
    {
        $response->setCookie(self::CONSENT_ID_COOKIE, $consentUuid, self::COOKIE_TTL, '', '/', '', null, true, 'Lax');
        $response->setCookie(self::CONSENT_STATE_COOKIE, $this->encodeStateCookie($state), self::COOKIE_TTL, '', '/', '', null, true, 'Lax');

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function privacyDocumentContext(): array
    {
        return [
            'policyVersion' => self::POLICY_VERSION,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentState(?int $userId): array
    {
        $cookieState = $this->stateFromCookies();

        if ($cookieState !== null) {
            return $cookieState;
        }

        if ($userId !== null) {
            $record = ($this->privacyConsentModel ?? new PrivacyConsentModel($this->db ?? Database::connect()))->latestActiveByUserId($userId);
            if ($record !== null) {
                return $this->normalizeState([
                    'version' => (string) ($record['policy_version'] ?? self::POLICY_VERSION),
                    'necessary' => (bool) ($record['necessary'] ?? true),
                    'preferences' => (bool) ($record['preferences'] ?? false),
                    'analytics' => (bool) ($record['analytics'] ?? false),
                    'marketing' => (bool) ($record['marketing'] ?? false),
                    'consented_at' => (string) ($record['consented_at'] ?? ''),
                    'has_choice' => true,
                ]);
            }
        }

        return $this->normalizeState([]);
    }

    /**
     * @param array<string, bool> $choices
     * @return array{consent_uuid:string, state: array<string, mixed>}
     */
    private function recordConsent(?int $userId, string $locale, array $choices, string $source): array
    {
        $model = $this->privacyConsentModel ?? new PrivacyConsentModel($this->db ?? Database::connect());
        $existingConsentId = $this->requestCookie(self::CONSENT_ID_COOKIE);
        $consentUuid = is_string($existingConsentId) && preg_match('/^[a-f0-9-]{36}$/i', $existingConsentId) === 1
            ? strtolower($existingConsentId)
            : $this->generateUuid();
        $now = Time::now()->toDateTimeString();

        $activeRecord = $model->activeByConsentUuid($consentUuid);
        if ($activeRecord !== null) {
            $model->update((int) $activeRecord['id'], [
                'withdrawn_at' => $now,
            ]);
        }

        $model->insert([
            'consent_uuid' => $consentUuid,
            'user_id' => $userId,
            'locale' => in_array($locale, ['it', 'en'], true) ? $locale : 'it',
            'policy_version' => self::POLICY_VERSION,
            'consent_source' => $source,
            'necessary' => 1,
            'preferences' => $choices['preferences'] ? 1 : 0,
            'analytics' => $choices['analytics'] ? 1 : 0,
            'marketing' => $choices['marketing'] ? 1 : 0,
            'consented_at' => $now,
            'withdrawn_at' => null,
        ]);

        return [
            'consent_uuid' => $consentUuid,
            'state' => $this->normalizeState([
                'version' => self::POLICY_VERSION,
                'necessary' => true,
                'preferences' => $choices['preferences'],
                'analytics' => $choices['analytics'],
                'marketing' => $choices['marketing'],
                'consented_at' => $now,
                'has_choice' => true,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stateFromCookies(): ?array
    {
        $raw = $this->requestCookie(self::CONSENT_STATE_COOKIE);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = $this->decodeStateCookie($raw);
        if ($decoded === null) {
            return null;
        }

        return $this->normalizeState($decoded);
    }

    private function requestCookie(string $name): ?string
    {
        $request = $this->request ?? service('request');
        $value = $request->getCookie($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function normalizeState(array $state): array
    {
        $version = is_string($state['version'] ?? null) ? (string) $state['version'] : self::POLICY_VERSION;
        $hasChoice = (bool) ($state['has_choice'] ?? false);

        if ($version !== self::POLICY_VERSION) {
            $hasChoice = false;
        }

        return [
            'version' => $version,
            'necessary' => true,
            'preferences' => (bool) ($state['preferences'] ?? false),
            'analytics' => (bool) ($state['analytics'] ?? false),
            'marketing' => (bool) ($state['marketing'] ?? false),
            'consented_at' => (string) ($state['consented_at'] ?? ''),
            'has_choice' => $hasChoice,
        ];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function encodeStateCookie(array $state): string
    {
        $json = json_encode([
            'version' => self::POLICY_VERSION,
            'necessary' => 1,
            'preferences' => ! empty($state['preferences']) ? 1 : 0,
            'analytics' => ! empty($state['analytics']) ? 1 : 0,
            'marketing' => ! empty($state['marketing']) ? 1 : 0,
            'consented_at' => (string) ($state['consented_at'] ?? ''),
            'has_choice' => ! empty($state['has_choice']) ? 1 : 0,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStateCookie(string $payload): ?array
    {
        $normalized = strtr($payload, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        try {
            $state = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($state) ? $state : null;
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

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
