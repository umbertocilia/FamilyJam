<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Auth\AuthTokenType;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\AuthTokenModel;
use App\Models\Auth\UserModel;
use App\Services\Audit\AuditLogService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class PasswordResetService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?AuthTokenService $authTokenService = null,
        private readonly ?OutboundEmailService $outboundEmailService = null,
        private readonly ?AuditLogService $auditLogService = null,
    ) {
    }

    public function request(string $email): void
    {
        $user = ($this->userModel ?? new UserModel())->findActiveByEmail($email);

        if ($user === null) {
            return;
        }

        $token = ($this->authTokenService ?? new AuthTokenService())->issue(
            (int) $user['id'],
            AuthTokenType::PASSWORD_RESET,
            date('Y-m-d H:i:s', strtotime('+2 hours')),
        );

        ($this->outboundEmailService ?? service('outboundEmail'))->sendPasswordReset($user, $token['token']);
        ($this->auditLogService ?? service('auditLogger'))->record(
            action: 'auth.password_reset_requested',
            entityType: 'user',
            entityId: (int) $user['id'],
            actorUserId: (int) $user['id'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function preview(string $rawToken): ?array
    {
        $token = ($this->authTokenService ?? new AuthTokenService())->findValid($rawToken, AuthTokenType::PASSWORD_RESET);

        if ($token === null) {
            return null;
        }

        $user = ($this->userModel ?? new UserModel())->find((int) $token['user_id']);

        if ($user === null) {
            return null;
        }

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function reset(string $rawToken, string $newPassword): ?array
    {
        $preview = $this->preview($rawToken);

        if ($preview === null) {
            return null;
        }

        $db = $this->db ?? Database::connect();
        $userModel = $this->userModel ?? new UserModel($db);
        $authTokenService = $this->authTokenService ?? new AuthTokenService(new AuthTokenModel($db));
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));

        $db->transException(true)->transStart();

        $userModel->update((int) $preview['user']['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $authTokenService->markUsed((int) $preview['token']['id']);
        $authTokenService->revokeAll((int) $preview['user']['id'], AuthTokenType::PASSWORD_RESET);

        $auditLogService->record(
            action: 'auth.password_reset_completed',
            entityType: 'user',
            entityId: (int) $preview['user']['id'],
            actorUserId: (int) $preview['user']['id'],
        );

        $db->transComplete();

        return $userModel->find((int) $preview['user']['id']);
    }
}
