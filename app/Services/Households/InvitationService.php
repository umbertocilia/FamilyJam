<?php

declare(strict_types=1);

namespace App\Services\Households;

use App\Authorization\Permission;
use App\Authorization\SystemRole;
use App\Models\Audit\AuditLogModel;
use App\Models\Auth\UserModel;
use App\Models\Auth\UserPreferenceModel;
use App\Models\Authorization\RoleModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Models\Households\InvitationModel;
use App\Models\Households\MembershipRoleModel;
use App\Services\Audit\AuditLogService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Auth\OutboundEmailService;
use App\Services\Notifications\NotificationService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use DomainException;

final class InvitationService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?InvitationModel $invitationModel = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?MembershipRoleModel $membershipRoleModel = null,
        private readonly ?RoleModel $roleModel = null,
        private readonly ?UserPreferenceModel $userPreferenceModel = null,
        private readonly ?HouseholdContextService $householdContextService = null,
        private readonly ?OutboundEmailService $outboundEmailService = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $actorUserId, string $identifier, array $payload): array
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $actorMembership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($actorMembership === null || ! $authorization->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_MEMBERS)) {
            throw new DomainException('Non hai i permessi necessari per invitare nuovi membri.');
        }

        $db = $this->db ?? Database::connect();
        $invitationModel = $this->invitationModel ?? new InvitationModel($db);
        $userModel = $this->userModel ?? new UserModel($db);
        $membershipModel = $this->householdMembershipModel ?? new HouseholdMembershipModel($db);
        $roleModel = $this->roleModel ?? new RoleModel($db);
        $outboundEmailService = $this->outboundEmailService ?? service('outboundEmail');
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $notificationService = $this->notificationService ?? new NotificationService($db);

        $email = strtolower(trim((string) $payload['email']));
        $roleCode = strtolower(trim((string) ($payload['role_code'] ?? SystemRole::MEMBER)));
        $role = $roleModel->findByCode($roleCode, (int) $actorMembership['household_id']);

        if ($role === null || (int) ($role['is_assignable'] ?? 0) !== 1) {
            throw new DomainException('Il ruolo selezionato non e assegnabile.');
        }

        $existingUser = $userModel->findByEmail($email, true);

        if ($existingUser !== null) {
            $existingMembership = $membershipModel->findAnyMembership((int) $actorMembership['household_id'], (int) $existingUser['id']);

            if ($existingMembership !== null && (string) ($existingMembership['status'] ?? '') === 'active' && empty($existingMembership['deleted_at'])) {
                throw new DomainException('Questo utente appartiene gia alla household selezionata.');
            }
        }

        $token = bin2hex(random_bytes(32));
        $now = Time::now();

        $db->transException(true)->transStart();

        $invitationModel->where('household_id', (int) $actorMembership['household_id'])
            ->where('email', $email)
            ->where('accepted_at', null)
            ->where('revoked_at', null)
            ->set(['revoked_at' => $now->toDateTimeString()])
            ->update();

        $invitationId = $invitationModel->insert([
            'household_id' => (int) $actorMembership['household_id'],
            'email' => $email,
            'role_id' => (int) $role['id'],
            'token_hash' => hash('sha256', $token),
            'invited_by_user_id' => $actorUserId,
            'message' => $this->nullableString($payload['message'] ?? null),
            'expires_at' => $now->addDays(7)->toDateTimeString(),
        ], true);

        $invitation = $invitationModel->findActiveByRawToken($token);
        $afterSnapshot = $this->sanitizeInvitationSnapshot($invitation ?? $invitationModel->find((int) $invitationId));

        $auditLogService->record(
            action: 'invitation.created',
            entityType: 'invitation',
            entityId: (int) $invitationId,
            actorUserId: $actorUserId,
            householdId: (int) $actorMembership['household_id'],
            after: $afterSnapshot + ['role_code' => $role['code']],
        );

        $db->transComplete();

        if ($invitation !== null) {
            $outboundEmailService->sendInvitation($invitation, $token);

            if ($existingUser !== null) {
                $notificationService->notifyInvitationReceived(
                    (int) $existingUser['id'],
                    (int) $actorMembership['household_id'],
                    (string) ($actorMembership['household_name'] ?? 'FamilyJam household'),
                    (string) ($role['name'] ?? $role['code']),
                    [
                        'accept_url' => site_url('invitations/accept/' . $token),
                        'household_slug' => (string) ($actorMembership['household_slug'] ?? ''),
                        'invitation_id' => (int) $invitation['id'],
                    ],
                );
            }
        }

        return $invitation ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function preview(string $rawToken): ?array
    {
        $invitation = ($this->invitationModel ?? new InvitationModel())->findActiveByRawToken($rawToken);

        if ($invitation === null) {
            return null;
        }

        $existingUser = ($this->userModel ?? new UserModel())->findByEmail((string) $invitation['email'], true);

        return [
            'invitation' => $invitation,
            'existingUser' => $existingUser,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function accept(string $rawToken, int $userId): ?array
    {
        $preview = $this->preview($rawToken);

        if ($preview === null) {
            return null;
        }

        $invitation = $preview['invitation'];
        $beforeInvitation = $this->sanitizeInvitationSnapshot($invitation);
        $user = ($this->userModel ?? new UserModel())->find($userId);

        if ($user === null || strtolower((string) $user['email']) !== strtolower((string) $invitation['email'])) {
            throw new DomainException('L\'invito non corrisponde all\'account autenticato.');
        }

        $db = $this->db ?? Database::connect();
        $membershipModel = $this->householdMembershipModel ?? new HouseholdMembershipModel($db);
        $membershipRoleModel = $this->membershipRoleModel ?? new MembershipRoleModel($db);
        $roleModel = $this->roleModel ?? new RoleModel($db);
        $userPreferenceModel = $this->userPreferenceModel ?? new UserPreferenceModel($db);
        $invitationModel = $this->invitationModel ?? new InvitationModel($db);
        $householdContextService = $this->householdContextService ?? service('householdContext');
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $now = Time::now()->toDateTimeString();

        $db->transException(true)->transStart();

        $membership = $membershipModel->findAnyMembership((int) $invitation['household_id'], $userId);

        if ($membership === null) {
            $membershipId = $membershipModel->insert([
                'household_id' => (int) $invitation['household_id'],
                'user_id' => $userId,
                'invited_by_user_id' => $invitation['invited_by_user_id'],
                'status' => 'active',
                'nickname' => null,
                'joined_at' => $now,
            ], true);
        } else {
            $membershipId = (int) $membership['id'];
            $membershipModel->withDeleted()->update($membershipId, [
                'invited_by_user_id' => $invitation['invited_by_user_id'],
                'status' => 'active',
                'joined_at' => $now,
                'deleted_at' => null,
            ]);
        }

        $role = null;

        if (! empty($invitation['role_id'])) {
            $role = $roleModel->find((int) $invitation['role_id']);
        }

        if ($role === null) {
            $role = $roleModel->findByCode(SystemRole::MEMBER, (int) $invitation['household_id']);
        }

        if ($role === null) {
            throw new DomainException('Il ruolo associato all\'invito non e disponibile.');
        }

        // When a previously removed membership is restored, stale historical roles
        // must not silently come back with it.
        $membershipRoleModel->syncRoles(
            $membershipId,
            [(int) $role['id']],
            $invitation['invited_by_user_id'] === null ? null : (int) $invitation['invited_by_user_id'],
        );

        $invitationModel->update((int) $invitation['id'], [
            'accepted_at' => $now,
        ]);

        $preferences = $userPreferenceModel->findByUserId($userId);

        if ($preferences === null) {
            $userPreferenceModel->insert([
                'user_id' => $userId,
                'default_household_id' => (int) $invitation['household_id'],
                'notification_preferences_json' => null,
                'dashboard_preferences_json' => null,
            ]);
        } elseif (($preferences['default_household_id'] ?? null) === null) {
            $userPreferenceModel->update((int) $preferences['id'], [
                'default_household_id' => (int) $invitation['household_id'],
            ]);
        }

        $auditLogService->record(
            action: 'invitation.accepted',
            entityType: 'invitation',
            entityId: (int) $invitation['id'],
            actorUserId: $userId,
            householdId: (int) $invitation['household_id'],
            before: $beforeInvitation,
            after: $this->sanitizeInvitationSnapshot($invitationModel->find((int) $invitation['id'])) + [
                'membership_id' => $membershipId,
                'membership_status' => 'active',
            ],
        );

        $db->transComplete();

        $resolvedMembership = $membershipModel->findActiveMembership((int) $invitation['household_id'], $userId);

        if ($resolvedMembership !== null) {
            $householdContextService->setActiveHousehold((string) $resolvedMembership['household_slug']);
        }

        return $resolvedMembership;
    }

    public function revoke(int $actorUserId, string $identifier, int $invitationId): void
    {
        $authorization = $this->householdAuthorizationService ?? service('householdAuthorization');
        $membership = $authorization->membershipByIdentifier($actorUserId, $identifier);

        if ($membership === null || ! $authorization->canByIdentifier($actorUserId, $identifier, Permission::MANAGE_MEMBERS)) {
            throw new DomainException('Non hai i permessi necessari per revocare questo invito.');
        }

        $db = $this->db ?? Database::connect();
        $invitationModel = $this->invitationModel ?? new InvitationModel($db);
        $auditLogService = $this->auditLogService ?? new AuditLogService(new AuditLogModel($db));
        $invitation = $invitationModel->findForHousehold((int) $membership['household_id'], $invitationId);

        if ($invitation === null || $invitation['accepted_at'] !== null || $invitation['revoked_at'] !== null) {
            throw new DomainException('Invito non disponibile per la revoca.');
        }

        $before = $this->sanitizeInvitationSnapshot($invitation);

        $db->transException(true)->transStart();

        $invitationModel->update($invitationId, [
            'revoked_at' => Time::now()->toDateTimeString(),
        ]);

        $auditLogService->record(
            action: 'invitation.revoked',
            entityType: 'invitation',
            entityId: $invitationId,
            actorUserId: $actorUserId,
            householdId: (int) $membership['household_id'],
            before: $before,
            after: $this->sanitizeInvitationSnapshot($invitationModel->findForHousehold((int) $membership['household_id'], $invitationId)),
        );

        $db->transComplete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingForHousehold(int $userId, string $identifier): array
    {
        $membership = ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->membershipByIdentifier($userId, $identifier);

        if ($membership === null) {
            return [];
        }

        return ($this->invitationModel ?? new InvitationModel())->findPendingForHousehold((int) $membership['household_id']);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed>|null $invitation
     * @return array<string, mixed>
     */
    private function sanitizeInvitationSnapshot(?array $invitation): array
    {
        if ($invitation === null) {
            return [];
        }

        unset($invitation['token_hash']);

        return $invitation;
    }
}
