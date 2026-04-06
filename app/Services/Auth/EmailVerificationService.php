<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;

final class EmailVerificationService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?AuthTokenService $authTokenService = null,
        private readonly ?OutboundEmailService $outboundEmailService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    public function resend(int $userId): bool
    {
        $user = ($this->userModel ?? new UserModel())->find($userId);

        if ($user === null || ! empty($user['email_verified_at'])) {
            return false;
        }

        $token = ($this->authTokenService ?? new AuthTokenService())->issue(
            $userId,
            AuthTokenType::EMAIL_VERIFICATION,
            date('Y-m-d H:i:s', strtotime('+2 days')),
        );

        ($this->outboundEmailService ?? service('outboundEmail'))->sendEmailVerification($user, $token['token']);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $rawToken): ?array
    {
        $token = ($this->authTokenService ?? new AuthTokenService())->findValid($rawToken, AuthTokenType::EMAIL_VERIFICATION);

        if ($token === null) {
            return null;
        }

        $db = $this->db ?? Database::connect();
        $userModel = $this->userModel ?? new UserModel($db);
        $authTokenService = $this->authTokenService ?? new AuthTokenService(new AuthTokenModel($db));
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $user = $userModel->find((int) $token['user_id']);

        if ($user === null) {
            return null;
        }

        $db->transException(true)->transStart();

        if (empty($user['email_verified_at'])) {
            $userModel->update((int) $user['id'], [
                'email_verified_at' => Time::now()->toDateTimeString(),
            ]);
        }

        $authTokenService->markUsed((int) $token['id']);

        $auditLogService->record(
            action: 'auth.email_verified',
            entityType: 'user',
            entityId: (int) $user['id'],
            actorUserId: (int) $user['id'],
        );

        $db->transComplete();

        return $userModel->find((int) $user['id']);
    }
}
