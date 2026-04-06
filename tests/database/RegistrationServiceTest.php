<?php

declare(strict_types=1);

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\OutboundEmailService;
use App\Services\Auth\RegistrationService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class RegistrationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;

    public function testRegisterCreatesUserPreferencesAndVerificationToken(): void
    {
        $db = db_connect($this->DBGroup);
        $service = new RegistrationService(
            db: $db,
            userModel: new UserModel($db),
            userPreferenceModel: new UserPreferenceModel($db),
            authTokenService: new AuthTokenService(new AuthTokenModel($db)),
            outboundEmailService: new OutboundEmailService(),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $user = $service->register([
            'email' => 'new-user@example.test',
            'password' => 'SecurePass123',
            'display_name' => 'New User',
            'locale' => 'it',
            'theme' => 'system',
            'timezone' => 'Europe/Rome',
            'email_notifications' => '1',
        ]);

        $this->assertSame('new-user@example.test', $user['email']);
        $this->assertNotSame('SecurePass123', $user['password_hash']);

        $preferences = (new UserPreferenceModel($db))->findByUserId((int) $user['id']);
        $this->assertNotNull($preferences);
        $this->assertStringContainsString('email_notifications', (string) $preferences['notification_preferences_json']);

        $token = (new AuthTokenModel($db))
            ->where('user_id', (int) $user['id'])
            ->where('type', AuthTokenType::EMAIL_VERIFICATION)
            ->first();

        $this->assertNotNull($token);
    }
}
