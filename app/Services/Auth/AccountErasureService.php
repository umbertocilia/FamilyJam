<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Auth\UserModel;
use App\Models\Households\HouseholdMembershipModel;
use App\Services\Audit\AuditLogService;
use App\Services\Media\AvatarImageService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use DomainException;

final class AccountErasureService
{
    public function __construct(
        private readonly ?BaseConnection $db = null,
        private readonly ?UserModel $userModel = null,
        private readonly ?HouseholdMembershipModel $householdMembershipModel = null,
        private readonly ?AuditLogService $auditLogService = null,
        private readonly ?AvatarImageService $avatarImageService = null,
    ) {
    }

    public function eraseSelf(int $userId, string $currentPassword, string $locale = 'en'): void
    {
        helper('ui');

        $db = $this->db ?? Database::connect();
        $userModel = $this->userModel ?? new UserModel($db);
        $householdMembershipModel = $this->householdMembershipModel ?? new HouseholdMembershipModel($db);
        $auditLogService = $this->auditLogService ?? service('auditLogger');
        $avatarImageService = $this->avatarImageService ?? service('avatarImages');

        $currentUser = $userModel->findActiveById($userId);

        if ($currentUser === null) {
            throw new DomainException(ui_text('profile.delete.unavailable', [], $locale));
        }

        if (! password_verify($currentPassword, (string) ($currentUser['password_hash'] ?? ''))) {
            throw new DomainException(ui_text('profile.delete.password_invalid', [], $locale));
        }

        $timestamp = Time::now();
        $now = $timestamp->toDateTimeString();
        $anonymizedEmail = sprintf('deleted+u%d.%s@familyjam.invalid', $userId, $timestamp->format('YmdHis'));
        $anonymizedName = ui_text('profile.delete.anonymized_name', ['id' => $userId], $locale);
        $membershipRows = $householdMembershipModel->withDeleted()->where('user_id', $userId)->findAll();
        $membershipIds = array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $membershipRows,
        ));

        $before = [
            'email' => (string) ($currentUser['email'] ?? ''),
            'display_name' => (string) ($currentUser['display_name'] ?? ''),
            'status' => (string) ($currentUser['status'] ?? 'active'),
            'membership_count' => count($membershipIds),
        ];

        $db->transException(true)->transStart();

        $avatarImageService->deleteManagedAvatar(isset($currentUser['avatar_path']) ? (string) $currentUser['avatar_path'] : null);

        $db->table('auth_tokens')->where('user_id', $userId)->delete();
        $db->table('notifications')->where('user_id', $userId)->delete();
        $db->table('privacy_consents')->where('user_id', $userId)->delete();
        $db->table('user_preferences')->where('user_id', $userId)->delete();

        if ($membershipIds !== []) {
            $db->table('membership_roles')->whereIn('membership_id', $membershipIds)->delete();
        }

        $db->table('membership_roles')
            ->where('assigned_by_user_id', $userId)
            ->set(['assigned_by_user_id' => null])
            ->update();

        $db->table('household_memberships')
            ->where('invited_by_user_id', $userId)
            ->set(['invited_by_user_id' => null])
            ->update();

        $db->table('household_memberships')
            ->where('user_id', $userId)
            ->set([
                'status' => 'removed',
                'nickname' => $anonymizedName,
                'invited_by_user_id' => null,
                'updated_at' => $now,
                'deleted_at' => $now,
            ])
            ->update();

        $db->table('invitations')
            ->where('invited_by_user_id', $userId)
            ->set([
                'invited_by_user_id' => null,
                'updated_at' => $now,
            ])
            ->update();

        $db->table('invitations')
            ->where('email', strtolower((string) ($currentUser['email'] ?? '')))
            ->set([
                'email' => $anonymizedEmail,
                'revoked_at' => $now,
                'updated_at' => $now,
            ])
            ->update();

        $db->table('audit_logs')
            ->where('actor_user_id', $userId)
            ->set(['actor_user_id' => null])
            ->update();

        $userModel->update($userId, [
            'email' => $anonymizedEmail,
            'password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'first_name' => null,
            'last_name' => null,
            'display_name' => $anonymizedName,
            'avatar_path' => null,
            'locale' => 'en',
            'theme' => 'system',
            'timezone' => 'UTC',
            'email_verified_at' => null,
            'last_login_at' => null,
            'status' => 'deleted',
        ]);

        $userModel->delete($userId);

        $auditLogService->record(
            action: 'user.account_erased',
            entityType: 'user',
            entityId: $userId,
            actorUserId: null,
            before: $before,
            after: [
                'email' => $anonymizedEmail,
                'display_name' => $anonymizedName,
                'status' => 'deleted',
                'memberships_removed' => count($membershipIds),
            ],
        );

        $db->transComplete();
    }
}
