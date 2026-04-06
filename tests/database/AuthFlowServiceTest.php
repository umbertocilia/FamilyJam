<?php

declare(strict_types=1);

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\LoginThrottleService;
use App\Services\Auth\OutboundEmailService;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\SessionAuthService;
use App\Services\Households\HouseholdContextService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class AuthFlowServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;

    public function testRegisterVerifyLoginAndLogoutFlowPersistsAuditAndSessionState(): void
    {
        $db = db_connect($this->DBGroup);
        $session = service('session');
        $session->remove(['auth.user_id', 'auth.logged_in_at', 'auth.fingerprint', 'app.active_household']);

        $registration = new RegistrationService(
            db: $db,
            userModel: new UserModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            authTokenService: new AuthTokenService(new AuthTokenModel($db)),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $emailVerification = new EmailVerificationService(
            db: $db,
            userModel: new UserModel($db),
            authTokenService: new AuthTokenService(new AuthTokenModel($db)),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $sessionAuth = new SessionAuthService(
            userModel: new UserModel($db),
            session: $session,
            loginThrottleService: new LoginThrottleService(),
            householdContextService: new HouseholdContextService(session: $session),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $user = $registration->register([
            'email' => 'auth-flow@example.test',
            'password' => 'SecurePass123',
            'display_name' => 'Auth Flow',
            'locale' => 'it',
            'theme' => 'system',
            'timezone' => 'Europe/Rome',
            'email_notifications' => '1',
        ]);

        $this->assertSame('auth-flow@example.test', $user['email']);

        $tokenRow = (new AuthTokenModel($db))
            ->where('user_id', (int) $user['id'])
            ->where('type', AuthTokenType::EMAIL_VERIFICATION)
            ->first();

        $this->assertNotNull($tokenRow);

        $rawToken = 'verify-auth-flow-token';
        (new AuthTokenModel($db))->update((int) $tokenRow['id'], [
            'token_hash' => hash('sha256', $rawToken),
        ]);

        $verifiedUser = $emailVerification->verify($rawToken);

        $this->assertNotNull($verifiedUser);
        $this->assertNotNull($verifiedUser['email_verified_at']);

        $loggedInUser = $sessionAuth->attempt('auth-flow@example.test', 'SecurePass123');

        $this->assertNotNull($loggedInUser);
        $this->assertSame((int) $user['id'], $session->get('auth.user_id'));
        $this->assertNotEmpty($session->get('auth.fingerprint'));
        $this->assertTrue($sessionAuth->hasValidSession());

        $sessionAuth->logout();

        $this->assertNull($session->get('auth.user_id'));
        $this->assertNull($session->get('auth.fingerprint'));
        $this->assertFalse($sessionAuth->hasValidSession());

        $auditActions = array_map(
            static fn (array $row): string => (string) $row['action'],
            (new AuditLogModel($db))
                ->where('entity_type', 'user')
                ->where('entity_id', (int) $user['id'])
                ->orderBy('id', 'ASC')
                ->findAll(),
        );

        $this->assertContains('auth.registered', $auditActions);
        $this->assertContains('auth.email_verified', $auditActions);
        $this->assertContains('auth.login', $auditActions);
        $this->assertContains('auth.logout', $auditActions);
    }

    public function testFailedLoginAttemptsAreThrottled(): void
    {
        $db = db_connect($this->DBGroup);
        $session = service('session');
        $session->remove(['auth.user_id', 'auth.logged_in_at', 'auth.fingerprint']);

        $userId = (int) (new UserModel($db))->insert([
            'email' => 'throttle@example.test',
            'password_hash' => password_hash('SecurePass123', PASSWORD_DEFAULT),
            'display_name' => 'Throttle User',
            'locale' => 'it',
            'theme' => 'system',
            'timezone' => 'Europe/Rome',
            'status' => 'active',
        ], true);

        $this->assertGreaterThan(0, $userId);

        $loginThrottle = new LoginThrottleService();
        $email = 'throttle@example.test';
        $ipAddress = $this->requestIp();
        $loginThrottle->clear($email, $ipAddress);

        $sessionAuth = new SessionAuthService(
            userModel: new UserModel($db),
            session: $session,
            loginThrottleService: $loginThrottle,
            householdContextService: new HouseholdContextService(session: $session),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->assertNull($sessionAuth->attempt($email, 'WrongPassword'));
        }

        $this->assertTrue($loginThrottle->blocked($email, $ipAddress));
        $this->assertNull($sessionAuth->attempt($email, 'SecurePass123'));
    }

    private function requestIp(): string
    {
        $request = service('request');

        return method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : 'cli';
    }
}
