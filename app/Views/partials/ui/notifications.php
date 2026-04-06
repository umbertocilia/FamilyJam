<li class="nav-item dropdown" data-notification-menu data-notification-poll-url="<?= $currentUserId === null ? '' : esc((string) ($notificationUi['pollUrl'] ?? '')) ?>">
    <a class="nav-link" data-toggle="dropdown" href="#" aria-label="<?= esc(ui_text('shell.notifications')) ?>">
        <i class="far fa-bell"></i>
        <?php if (($appShell['uiNotifications']['unreadCount'] ?? 0) > 0): ?>
            <span class="badge badge-warning navbar-badge" data-notification-badge><?= esc((string) $appShell['uiNotifications']['unreadCount']) ?></span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header" data-notification-header>
            <?= esc(ui_text('notification.center.title')) ?>
            <?php if (($appShell['uiNotifications']['unreadCount'] ?? 0) > 0): ?>
                (<?= esc((string) $appShell['uiNotifications']['unreadCount']) ?>)
            <?php endif; ?>
        </span>
        <div class="dropdown-divider"></div>
        <div data-notification-items>
            <?php if (($appShell['uiNotifications']['items'] ?? []) === []): ?>
                <span class="dropdown-item text-muted" data-notification-empty><?= esc(ui_text('notification.empty.title')) ?></span>
                <div class="dropdown-divider"></div>
            <?php else: ?>
                <?php foreach ($appShell['uiNotifications']['items'] as $notification): ?>
                    <a href="<?= esc((string) ($notification['target_url'] ?? $appShell['uiNotifications']['centerUrl'])) ?>" class="dropdown-item">
                        <i class="fas fa-circle text-warning mr-2"></i>
                        <?= esc((string) $notification['title']) ?>
                        <span class="float-right text-muted text-sm"><?= esc((string) ($notification['created_at'] ?? '')) ?></span>
                    </a>
                    <div class="dropdown-divider"></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($currentUserId !== null): ?>
            <form method="post" action="<?= esc((string) $appShell['uiNotifications']['markAllUrl']) ?>" data-notification-mark-all-form <?= ($appShell['uiNotifications']['unreadCount'] ?? 0) > 0 ? '' : 'hidden' ?>>
                <?= csrf_field() ?>
                <button class="dropdown-item dropdown-footer" type="submit"><?= esc(ui_text('notification.mark_all')) ?></button>
            </form>
            <div class="dropdown-divider" data-notification-mark-all-divider <?= ($appShell['uiNotifications']['unreadCount'] ?? 0) > 0 ? '' : 'hidden' ?>></div>
        <?php endif; ?>
        <a href="<?= esc((string) $appShell['uiNotifications']['centerUrl']) ?>" class="dropdown-item dropdown-footer" data-notification-center-link><?= esc(ui_text('notification.open_center')) ?></a>
    </div>
</li>
