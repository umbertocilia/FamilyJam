<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$scope = $notificationCenterContext['scope'];
$notifications = $notificationCenterContext['notifications'];
$unreadCount = (int) $notificationCenterContext['unread_count'];
$markAllUrl = $notificationCenterContext['mark_all_url'];
$showHistory = service('request')->getGet('history') === '1';
$queryString = service('request')->getUri()->getQuery();
$redirectTo = current_url() . ($queryString !== '' ? '?' . $queryString : '');
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('shell.notifications')) ?></p>
        <h1><?= esc(ui_text('notification.center.title')) ?></h1>
        <p class="hero__lead"><?= esc(ui_text('notification.center.lead')) ?></p>
        <div class="quick-filter-bar" aria-label="<?= esc(ui_locale() === 'it' ? 'Filtri centro notifiche' : 'Notification center filters') ?>">
            <span class="badge badge--expense-step"><?= esc((string) $scope['label']) ?></span>
            <span class="badge <?= $unreadCount > 0 ? 'badge--success' : 'badge--expense-step' ?>"><?= esc((string) $unreadCount) ?> <?= esc(ui_text('common.unread')) ?></span>
        </div>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= esc($scope['filter_url']) ?>"><?= esc(ui_text('common.all')) ?></a>
        <a class="button button--secondary" href="<?= esc($scope['unread_url']) ?>"><?= esc(ui_text('notification.unread_only')) ?></a>
        <a class="button button--secondary" href="<?= esc($scope['filter_url']) ?>?history=1"><?= esc(ui_text('notification.show_history')) ?></a>
        <?php if ($unreadCount > 0): ?>
            <form method="post" action="<?= esc($markAllUrl) ?>">
                <?= csrf_field() ?>
                <button class="button button--primary" type="submit"><?= esc(ui_text('notification.mark_all')) ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span><?= esc(ui_text('common.filter')) ?></span><strong><?= esc((string) $scope['label']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_text('common.unread')) ?></span><strong><?= esc((string) $unreadCount) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Flusso' : 'Feed') ?></span><strong><?= esc((string) count($notifications)) ?></strong><small><?= esc($showHistory ? ui_text('notification.show_history') : ui_text('notification.unread_only')) ?></small></article>
</section>

<section class="stack">
    <?php if ($notifications === []): ?>
        <article class="panel">
            <?php $title = ui_text('notification.empty.title'); $message = ui_text('notification.empty.message'); $actionLabel = null; $actionHref = null; ?>
            <?= $this->include('partials/components/empty_state') ?>
        </article>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <article class="panel notification-feed-card <?= empty($notification['read_at']) ? 'notification-feed-card--unread' : '' ?>">
                <div class="expense-row__header">
                    <div class="stack stack--compact">
                        <div class="expense-row__meta">
                            <span class="<?= esc(notification_badge_class($notification)) ?>"><?= esc(notification_type_label((string) $notification['type'])) ?></span>
                            <?php if (! empty($notification['household_name'])): ?>
                                <span class="badge badge--expense-step"><?= esc((string) $notification['household_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <strong><?= esc((string) $notification['title']) ?></strong>
                        <?php if (! empty($notification['body'])): ?>
                            <p><?= esc((string) $notification['body']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span><?= esc((string) $notification['created_at']) ?></span>
                </div>

                <div class="hero__actions">
                    <?php if (! empty($notification['target_url'])): ?>
                        <a class="button button--primary" href="<?= esc((string) $notification['target_url']) ?>"><?= esc(ui_text('common.open')) ?></a>
                    <?php endif; ?>
                        <?php if (empty($notification['read_at'])): ?>
                        <form method="post" action="<?= route_url('notifications.read', $notification['id']) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="<?= esc($redirectTo) ?>">
                            <button class="button button--secondary" type="submit"><?= esc(ui_text('notification.mark_read')) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
