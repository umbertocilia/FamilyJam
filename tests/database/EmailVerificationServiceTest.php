<?php

declare(strict_types=1);

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\EmailVerificationService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class EmailVerificationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $DBGroup = 'tests';
    protected $migrate = true;
    protected $refresh = true;

    public function testVerifyMarksEmailAsVerified(): void
    {
        $db = db_connect($this->DBGroup);
        $userId = (int) (new UserModel($db))->insert([
            'email' => 'verify-me@example.test',
            'password_hash' => password_hash('SecurePass123', PASSWORD_DEFAULT),
            'display_name' => 'Verify Me',
            'locale' => 'it',
            'theme' => 'system',
            'timezone' => 'Europe/Rome',
            'status' => 'active',
        ], true);

        $token = (new AuthTokenService(new AuthTokenModel($db)))->issue(
            $userId,
            AuthTokenType::EMAIL_VERIFICATION,
            date('Y-m-d H:i:s', strtotime('+1 day')),
        );

        $service = new EmailVerificationService(
            db: $db,
            userModel: new UserModel($db),
            authTokenService: new AuthTokenService(new AuthTokenModel($db)),
            auditLogService: new AuditLogService(new AuditLogModel($db)),
        );

        $user = $service->verify($token['token']);

        $this->assertNotNull($user);
        $this->assertNotEmpty($user['email_verified_at']);
    }
}
