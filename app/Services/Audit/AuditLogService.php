<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\Audit\AuditLogModel;
use CodeIgniter\I18n\Time;
use Throwable;

final class AuditLogService
{
    public function __construct(private readonly ?AuditLogModel $auditLogModel = null)
    {
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?int $actorUserId = null,
        ?int $householdId = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        try {
            $model = $this->auditLogModel ?? new AuditLogModel();
            $request = service('request');

            if ($ipAddress === null && method_exists($request, 'getIPAddress')) {
                $ipAddress = $request->getIPAddress();
            }

            if ($userAgent === null && method_exists($request, 'getUserAgent')) {
                $agent = $request->getUserAgent();
                $userAgent = $agent === null ? null : $agent->getAgentString();
            }

            $afterPayload = $after ?? ($metadata === [] ? null : $metadata);

            $model->insert([
                'household_id' => $householdId,
                'actor_user_id' => $actorUserId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'before_json' => $before === null || $before === [] ? null : json_encode($before, JSON_THROW_ON_ERROR),
                'after_json' => $afterPayload === null || $afterPayload === [] ? null : json_encode($afterPayload, JSON_THROW_ON_ERROR),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => Time::now()->toDateTimeString(),
            ]);
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam audit] record() failed for {action}/{entity}: {message}', [
                'action' => $action,
                'entity' => $entityType,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
