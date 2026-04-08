<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Auth\UserModel;
use App\Services\Audit\AuditLogService;
use App\Services\Households\HouseholdContextService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;

final class SessionAuthService
{
    private const REMEMBER_COOKIE = 'familyjam_remember';
    private const REMEMBER_TOKEN_TYPE = 'remember_login';
    private const REMEMBER_LIFETIME_DAYS = 30;

    public function __construct(
        private readonly ?UserModel $userModel = null,
        private readonly ?Session $session = null,
        private readonly ?LoginThrottleService $loginThrottleService = null,
        private readonly ?HouseholdContextService $householdContextService = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?AuthTokenService $authTokenService = null,
    ) {
    }

    public function attempt(string $email, string $password, bool $remember = false): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $userModel = $this->userModel ?? new UserModel();
        $loginThrottleService = $this->loginThrottleService ?? service('loginThrottle');
        $auditLogService = $this->auditLogService ?? service('auditLogger');
        $request = service('request');
        $ipAddress = method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : 'cli';

        if ($loginThrottleService->blocked($normalizedEmail, $ipAddress)) {
            return null;
        }

        $user = $userModel->findActiveByEmail($normalizedEmail);

        if ($user === null || ! password_verify($password, (string) $user['password_hash'])) {
            $loginThrottleService->increment($normalizedEmail, $ipAddress);
            $auditLogService->record(
                action: 'auth.login_failed',
                entityType: 'user',
                entityId: $user === null ? null : (int) $user['id'],
                metadata: ['email' => $normalizedEmail],
            );

            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $userModel->update((int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        $timestamp = Time::now()->toDateTimeString();

        $userModel->update((int) $user['id'], [
            'last_login_at' => $timestamp,
        ]);

        $this->establishSession($user, $timestamp);
        $this->syncRememberCookie((int) $user['id'], $remember);
        $loginThrottleService->clear($normalizedEmail, $ipAddress);

        $auditLogService->record(
            action: 'auth.login',
            entityType: 'user',
            entityId: (int) $user['id'],
            actorUserId: (int) $user['id'],
            metadata: ['remember_me' => $remember],
        );

        return $userModel->find((int) $user['id']);
    }

    public function logout(bool $revokeRemember = true): void
    {
        $session = $this->session ?? session();
        $userId = $this->currentSessionUserId();

        if ($userId !== null) {
            ($this->auditLogService ?? service('auditLogger'))->record(
                action: 'auth.logout',
                entityType: 'user',
                entityId: $userId,
                actorUserId: $userId,
            );
        }

        if ($revokeRemember && $userId !== null) {
            ($this->authTokenService ?? service('authToken'))->revokeAll($userId, self::REMEMBER_TOKEN_TYPE);
        }

        $session->remove([
            'auth.user_id',
            'auth.logged_in_at',
            'auth.fingerprint',
            'auth.intended_url',
            'auth.pending_invitation_token',
            'app.locale',
            'app.theme',
            'app.active_household',
        ]);

        ($this->householdContextService ?? service('householdContext'))->clearActiveHousehold();
        $session->regenerate(true);

        if ($revokeRemember) {
            $this->clearRememberCookie();
        }
    }

    public function currentUserId(): ?int
    {
        $session = $this->session ?? session();
        $userId = $session->get('auth.user_id');

        if ($userId === null && $this->restoreRememberedSession()) {
            $userId = $session->get('auth.user_id');
        }

        return $userId === null ? null : (int) $userId;
    }

    public function currentUser(): ?array
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return null;
        }

        return ($this->userModel ?? new UserModel())->findActiveById($userId);
    }

    public function hasValidSession(): bool
    {
        $sessionUserId = $this->currentSessionUserId();

        if ($sessionUserId === null) {
            return $this->restoreRememberedSession();
        }

        $user = ($this->userModel ?? new UserModel())->findActiveById($sessionUserId);

        if ($user === null) {
            return false;
        }

        $session = $this->session ?? session();
        $fingerprint = $session->get('auth.fingerprint');
        $currentFingerprint = $this->fingerprintForCurrentRequest();

        if (! is_string($fingerprint) || $fingerprint === '') {
            $session->set('auth.fingerprint', $currentFingerprint);

            return true;
        }

        if ($currentFingerprint === '') {
            return true;
        }

        if (! hash_equals($fingerprint, $currentFingerprint)) {
            $session->set('auth.fingerprint', $currentFingerprint);
        }

        return true;
    }

    public function restoreRememberedSession(): bool
    {
        $session = $this->session ?? session();

        if ($session->get('auth.user_id') !== null) {
            return true;
        }

        $rawToken = trim((string) service('request')->getCookie(self::REMEMBER_COOKIE));

        if ($rawToken === '') {
            return false;
        }

        $token = ($this->authTokenService ?? service('authToken'))->findValid($rawToken, self::REMEMBER_TOKEN_TYPE);

        if ($token === null) {
            $this->clearRememberCookie();

            return false;
        }

        $user = ($this->userModel ?? new UserModel())->findActiveById((int) $token['user_id']);

        if ($user === null) {
            ($this->authTokenService ?? service('authToken'))->revokeAll((int) $token['user_id'], self::REMEMBER_TOKEN_TYPE);
            $this->clearRememberCookie();

            return false;
        }

        $timestamp = Time::now()->toDateTimeString();

        $this->establishSession($user, $timestamp);
        $this->syncRememberCookie((int) $user['id'], true);

        ($this->auditLogService ?? service('auditLogger'))->record(
            action: 'auth.login_remembered',
            entityType: 'user',
            entityId: (int) $user['id'],
            actorUserId: (int) $user['id'],
        );

        return true;
    }

    private function fingerprintForCurrentRequest(): string
    {
        $request = service('request');
        $agentString = trim((string) $request->getHeaderLine('User-Agent'));

        if ($agentString === '' && method_exists($request, 'getUserAgent') && $request->getUserAgent() !== null) {
            $agentString = trim((string) $request->getUserAgent()->getAgentString());
        }

        return hash('sha256', $agentString);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function establishSession(array $user, string $timestamp): void
    {
        $session = $this->session ?? session();
        $session->regenerate(true);
        $session->set('auth.user_id', (int) $user['id']);
        $session->set('auth.logged_in_at', $timestamp);
        $session->set('auth.fingerprint', $this->fingerprintForCurrentRequest());
        $session->set('app.locale', in_array((string) ($user['locale'] ?? ''), ['it', 'en'], true) ? (string) $user['locale'] : 'en');
        $session->set('app.theme', in_array((string) ($user['theme'] ?? ''), ['system', 'light', 'dark'], true) ? (string) $user['theme'] : 'system');
    }

    private function currentSessionUserId(): ?int
    {
        $userId = ($this->session ?? session())->get('auth.user_id');

        return $userId === null ? null : (int) $userId;
    }

    private function syncRememberCookie(int $userId, bool $remember): void
    {
        $tokenService = $this->authTokenService ?? service('authToken');

        if (! $remember) {
            $tokenService->revokeAll($userId, self::REMEMBER_TOKEN_TYPE);
            $this->clearRememberCookie();

            return;
        }

        $expiresAt = Time::now()->addDays(self::REMEMBER_LIFETIME_DAYS)->toDateTimeString();
        $issued = $tokenService->issue($userId, self::REMEMBER_TOKEN_TYPE, $expiresAt);

        service('response')->setCookie(
            self::REMEMBER_COOKIE,
            (string) $issued['token'],
            60 * 60 * 24 * self::REMEMBER_LIFETIME_DAYS,
            '',
            '/',
            '',
            true,
            true,
            'Lax',
        );
    }

    private function clearRememberCookie(): void
    {
        service('response')->deleteCookie(self::REMEMBER_COOKIE, '', '/');
    }
}
