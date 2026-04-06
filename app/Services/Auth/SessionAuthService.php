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
    public function __construct(
        private readonly ?UserModel $userModel = null,
        private readonly ?Session $session = null,
        private readonly ?LoginThrottleService $loginThrottleService = null,
        private readonly ?HouseholdContextService $householdContextService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    public function attempt(string $email, string $password): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $userModel = $this->userModel ?? new UserModel();
        $session = $this->session ?? session();
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

        $session->regenerate(true);
        $session->set('auth.user_id', (int) $user['id']);
        $session->set('auth.logged_in_at', $timestamp);
        $session->set('auth.fingerprint', $this->fingerprintForCurrentRequest());
        $session->set('app.locale', in_array((string) ($user['locale'] ?? ''), ['it', 'en'], true) ? (string) $user['locale'] : 'en');
        $session->set('app.theme', in_array((string) ($user['theme'] ?? ''), ['system', 'light', 'dark'], true) ? (string) $user['theme'] : 'system');
        $loginThrottleService->clear($normalizedEmail, $ipAddress);

        $auditLogService->record(
            action: 'auth.login',
            entityType: 'user',
            entityId: (int) $user['id'],
            actorUserId: (int) $user['id'],
        );

        return $userModel->find((int) $user['id']);
    }

    public function logout(): void
    {
        $session = $this->session ?? session();
        $userId = $this->currentUserId();

        if ($userId !== null) {
            ($this->auditLogService ?? service('auditLogger'))->record(
                action: 'auth.logout',
                entityType: 'user',
                entityId: $userId,
                actorUserId: $userId,
            );
        }

        $session->remove([
            'auth.user_id',
            'auth.logged_in_at',
            'auth.fingerprint',
            'auth.intended_url',
            'auth.pending_invitation_token',
            'app.locale',
            'app.theme',
        ]);

        ($this->householdContextService ?? service('householdContext'))->clearActiveHousehold();
        $session->regenerate(true);
    }

    public function currentUserId(): ?int
    {
        $userId = ($this->session ?? session())->get('auth.user_id');

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
        $userId = $this->currentUserId();

        if ($userId === null) {
            return false;
        }

        $user = ($this->userModel ?? new UserModel())->findActiveById($userId);

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

    private function fingerprintForCurrentRequest(): string
    {
        $request = service('request');
        $agentString = trim((string) $request->getHeaderLine('User-Agent'));

        if ($agentString === '' && method_exists($request, 'getUserAgent') && $request->getUserAgent() !== null) {
            $agentString = trim((string) $request->getUserAgent()->getAgentString());
        }

        return hash('sha256', $agentString);
    }
}
