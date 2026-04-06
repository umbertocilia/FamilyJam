<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Auth\AuthTokenModel;
use CodeIgniter\I18n\Time;

final class AuthTokenService
{
    public function __construct(private readonly ?AuthTokenModel $authTokenModel = null)
    {
    }

    /**
     * @return array{token: string, record: array<string, mixed>|null}
     */
    public function issue(int $userId, string $type, ?string $expiresAt = null): array
    {
        $model = $this->authTokenModel ?? new AuthTokenModel();
        $request = service('request');
        $rawToken = bin2hex(random_bytes(32));

        $model->revokeOutstanding($userId, $type);

        $tokenId = $model->insert([
            'user_id' => $userId,
            'type' => $type,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => $expiresAt,
            'created_ip' => method_exists($request, 'getIPAddress') ? $request->getIPAddress() : null,
            'user_agent' => method_exists($request, 'getUserAgent') && $request->getUserAgent() !== null
                ? $request->getUserAgent()->getAgentString()
                : null,
        ], true);

        return [
            'token' => $rawToken,
            'record' => $model->find((int) $tokenId),
        ];
    }

    public function findValid(string $rawToken, string $type): ?array
    {
        $model = $this->authTokenModel ?? new AuthTokenModel();

        return $model->findValidByRawToken($rawToken, $type);
    }

    public function markUsed(int $tokenId): void
    {
        $model = $this->authTokenModel ?? new AuthTokenModel();

        $model->update($tokenId, ['used_at' => Time::now()->toDateTimeString()]);
    }

    public function revokeAll(int $userId, string $type): void
    {
        $model = $this->authTokenModel ?? new AuthTokenModel();
        $model->revokeOutstanding($userId, $type);
    }
}
