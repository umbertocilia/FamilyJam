<?php

declare(strict_types=1);

namespace App\Controllers\Web\Notifications;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

final class NotificationController extends BaseController
{
    public function accountIndex(): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        helper('ui');

        $context = service('notificationService')->centerContext(
            $this->currentUserId,
            null,
            null,
            ['unread_only' => $this->request->getGet('history') !== '1'],
        );

        return $this->render('notifications/index', [
            'pageClass' => 'notifications-page',
            'pageTitle' => 'Notifications | FamilyJam',
            'notificationCenterContext' => $context,
        ]);
    }

    public function index(string $identifier): string
    {
        if ($this->currentUserId === null || $this->activeHousehold === null) {
            throw SecurityException::forDisallowedAction();
        }

        helper('ui');

        $context = service('notificationService')->centerContext(
            $this->currentUserId,
            (int) $this->activeHousehold['household_id'],
            (string) ($this->activeHousehold['household_slug'] ?? $identifier),
            ['unread_only' => $this->request->getGet('history') !== '1'],
        );

        return $this->render('notifications/index', [
            'pageClass' => 'notifications-page',
            'pageTitle' => 'Notifications | FamilyJam',
            'notificationCenterContext' => $context,
        ]);
    }

    public function read(int $notificationId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $notification = service('notificationService')->markAsRead($this->currentUserId, $notificationId);
        $redirectTo = trim((string) $this->request->getPost('redirect_to'));

        if ($this->isSafeRedirect($redirectTo)) {
            return redirect()->to($redirectTo);
        }

        if ($notification !== null && ! empty($notification['target_url'])) {
            return redirect()->to((string) $notification['target_url']);
        }

        if ($this->activeHousehold !== null) {
            return redirect()->to(route_url('notifications.index', (string) $this->activeHousehold['household_slug']) . '?history=0');
        }

        return redirect()->to(route_url('notifications.global') . '?history=0');
    }

    public function readAll(?string $identifier = null): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        helper('ui');

        if ($this->activeHousehold !== null) {
            service('notificationService')->markAllAsRead(
                $this->currentUserId,
                (int) $this->activeHousehold['household_id'],
                true,
            );

            return redirect()->to(route_url('notifications.index', (string) $this->activeHousehold['household_slug']) . '?history=0')
                ->with('success', ui_text('notification.mark_all'));
        }

        service('notificationService')->markAllAsRead($this->currentUserId);

        return redirect()->to(route_url('notifications.global') . '?history=0')
            ->with('success', ui_text('notification.mark_all'));
    }

    public function poll(?string $identifier = null): ResponseInterface
    {
        if ($this->currentUserId === null) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'unauthorized']);
        }

        $householdId = $this->activeHousehold !== null && isset($this->activeHousehold['household_id'])
            ? (int) $this->activeHousehold['household_id']
            : null;
        $householdSlug = $this->activeHousehold !== null && isset($this->activeHousehold['household_slug'])
            ? (string) $this->activeHousehold['household_slug']
            : null;

        return $this->response->setJSON(
            service('notificationService')->drawerContext($this->currentUserId, $householdId, $householdSlug),
        );
    }

    private function isSafeRedirect(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return str_starts_with($value, site_url())
            || str_starts_with($value, base_url())
            || str_starts_with($value, '/');
    }
}
