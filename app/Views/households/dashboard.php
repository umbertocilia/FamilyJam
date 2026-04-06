<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $dashboardContext['membership'];
$household = $dashboardContext['household'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
$primaryBalance = $dashboardContext['primary_personal_balance'] ?? null;
$transferSummary = $dashboardContext['transfer_summary'];
$recentExpenses = $dashboardContext['recent_expenses'] ?? [];
$upcomingChores = $dashboardContext['upcoming_chores'] ?? [];
$urgentShopping = $dashboardContext['urgent_shopping_items'] ?? [];
$recentPosts = $dashboardContext['recent_posts'] ?? [];
$recentNotifications = $dashboardContext['recent_notifications'] ?? [];
$summary = $dashboardContext['summary'] ?? [];
$personalBalanceLabel = $primaryBalance === null
    ? '0.00 ' . (string) $household['base_currency']
    : money_format((string) $primaryBalance['net_amount'], (string) $primaryBalance['currency']);
?>

<section class="card card-primary card-outline dashboard-hero-card mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div class="dashboard-hero-copy">
                <div class="text-uppercase text-muted small fw-semibold mb-2"><?= esc(ui_locale() === 'it' ? 'Dashboard v2' : 'Dashboard v2') ?></div>
                <h1 class="display-5 fw-bold mb-3"><?= esc((string) $household['name']) ?></h1>
                <p class="lead text-muted mb-3">
                    <?= esc((string) ($household['description'] ?? (ui_locale() === 'it' ? 'Panoramica operativa di spese, saldi, chore, shopping e comunicazione household.' : 'Operational snapshot for expenses, balances, chores, shopping and household communication.'))) ?>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-light border"><?= esc((string) $household['base_currency']) ?></span>
                    <span class="badge text-bg-light border"><?= esc((string) $household['timezone']) ?></span>
                    <?php if (! empty($household['simplify_debts'])): ?>
                        <span class="badge text-bg-success"><?= esc(ui_text('dashboard.badge.debt_simplification')) ?></span>
                    <?php endif; ?>
                    <?php if (! empty($household['chore_scoring_enabled'])): ?>
                        <span class="badge text-bg-info"><?= esc(ui_locale() === 'it' ? 'Punteggio chore attivo' : 'Chore scoring enabled') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-hero-actions">
                <?php if ($dashboardContext['can_create_expense']): ?>
                    <a class="btn btn-primary btn-lg" href="<?= route_url('expenses.create', $identifier) ?>">
                        <i class="fas fa-plus-circle me-2"></i><?= esc(ui_locale() === 'it' ? 'Nuova spesa' : 'New expense') ?>
                    </a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-lg" href="<?= route_url('balances.overview', $identifier) ?>">
                    <i class="fas fa-wallet me-2"></i><?= esc(ui_text('nav.balances')) ?>
                </a>
                <?php if ($dashboardContext['can_view_reports']): ?>
                    <a class="btn btn-outline-secondary btn-lg" href="<?= route_url('reports.index', $identifier) ?>">
                        <i class="fas fa-chart-line me-2"></i><?= esc(ui_text('nav.reports')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="small-box text-bg-primary shadow-sm">
            <div class="inner">
                <h3><?= esc($personalBalanceLabel) ?></h3>
                <p><?= esc(ui_text('dashboard.metric.personal_balance')) ?></p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
            <a href="<?= route_url('balances.personal', $identifier) ?>" class="small-box-footer">
                <?= esc(ui_text('common.details')) ?> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="small-box text-bg-success shadow-sm">
            <div class="inner">
                <h3><?= esc((string) ($summary['open_transfers'] ?? 0)) ?></h3>
                <p><?= esc(ui_text('dashboard.metric.open_transfers')) ?></p>
            </div>
            <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            <a href="<?= route_url('balances.pairwise', $identifier) ?>" class="small-box-footer">
                <?= esc(ui_locale() === 'it' ? 'Ledger reale' : 'Real ledger') ?> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="small-box text-bg-warning shadow-sm">
            <div class="inner">
                <h3><?= esc((string) ($summary['open_chores'] ?? 0)) ?></h3>
                <p><?= esc(ui_text('dashboard.metric.open_chores')) ?></p>
            </div>
            <div class="icon"><i class="fas fa-check-square"></i></div>
            <a href="<?= route_url('chores.my', $identifier) ?>" class="small-box-footer">
                <?= esc(ui_locale() === 'it' ? 'Le mie chore' : 'My chores') ?> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="small-box text-bg-danger shadow-sm">
            <div class="inner">
                <h3><?= esc((string) ($summary['unread_notifications'] ?? 0)) ?></h3>
                <p><?= esc(ui_text('dashboard.metric.unread_notifications')) ?></p>
            </div>
            <div class="icon"><i class="fas fa-bell"></i></div>
            <a href="<?= route_url('notifications.index', $identifier) ?>" class="small-box-footer">
                <?= esc(ui_locale() === 'it' ? 'Centro notifiche' : 'Notification center') ?> <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card card-outline card-primary h-100 shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-semibold"><i class="fas fa-chart-pie me-2"></i><?= esc(ui_locale() === 'it' ? 'Riepilogo household mensile' : 'Monthly household recap') ?></h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <p class="text-muted mb-3"><?= esc((string) $transferSummary['note']) ?></p>
                        <div class="dashboard-transfer-list">
                            <?php if (($transferSummary['rows'] ?? []) === []): ?>
                                <div class="callout callout-success mb-0">
                                    <h5 class="mb-1"><?= esc(ui_text('dashboard.empty.transfer.title')) ?></h5>
                                    <p class="mb-0"><?= esc(ui_text('dashboard.empty.transfer.message')) ?></p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($transferSummary['rows'], 0, 4) as $row): ?>
                                    <div class="info-box mb-3">
                                        <span class="info-box-icon bg-light">
                                            <i class="fas fa-exchange-alt text-primary"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text"><?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?></span>
                                            <span class="info-box-number"><?= esc(money_format((string) $row['amount'], (string) $row['currency'])) ?></span>
                                            <span class="text-muted small"><?= esc($transferSummary['mode'] === 'simplified' ? (ui_locale() === 'it' ? 'Suggerimento semplificato' : 'Simplified suggestion') : (ui_locale() === 'it' ? 'Saldo reale' : 'Real balance')) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="dashboard-progress-list">
                            <div class="progress-group">
                                <?= esc(ui_locale() === 'it' ? 'Membri attivi' : 'Active members') ?>
                                <span class="float-end"><b><?= esc((string) $dashboardContext['members_count']) ?></b>/<?= esc((string) max(1, $dashboardContext['members_count'])) ?></span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar text-bg-primary" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="progress-group">
                                <?= esc(ui_locale() === 'it' ? 'Spese recenti' : 'Recent expenses') ?>
                                <span class="float-end"><b><?= esc((string) ($summary['recent_expenses'] ?? 0)) ?></b>/6</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar text-bg-success" style="width: <?= esc((string) min(100, (($summary['recent_expenses'] ?? 0) / 6) * 100)) ?>%"></div>
                                </div>
                            </div>
                            <div class="progress-group">
                                <?= esc(ui_text('dashboard.metric.urgent_items')) ?>
                                <span class="float-end"><b><?= esc((string) ($summary['urgent_items'] ?? 0)) ?></b>/6</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar text-bg-warning" style="width: <?= esc((string) min(100, (($summary['urgent_items'] ?? 0) / 6) * 100)) ?>%"></div>
                                </div>
                            </div>
                            <div class="progress-group mb-0">
                                <?= esc(ui_text('nav.notifications')) ?>
                                <span class="float-end"><b><?= esc((string) ($summary['unread_notifications'] ?? 0)) ?></b>/10</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar text-bg-danger" style="width: <?= esc((string) min(100, (($summary['unread_notifications'] ?? 0) / 10) * 100)) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card card-outline card-info h-100 shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-semibold"><i class="fas fa-bolt me-2"></i><?= esc(ui_locale() === 'it' ? 'Azioni rapide' : 'Quick actions') ?></h3>
            </div>
            <div class="card-body">
                <div class="dashboard-action-grid">
                    <a class="btn btn-outline-primary" href="<?= route_url('memberships.index', $identifier) ?>"><i class="fas fa-users me-2"></i><?= esc(ui_text('nav.members')) ?></a>
                    <a class="btn btn-outline-primary" href="<?= route_url('expenses.index', $identifier) ?>"><i class="fas fa-receipt me-2"></i><?= esc(ui_text('nav.expenses')) ?></a>
                    <a class="btn btn-outline-primary" href="<?= route_url('shopping.index', $identifier) ?>"><i class="fas fa-shopping-cart me-2"></i><?= esc(ui_text('nav.shopping')) ?></a>
                    <a class="btn btn-outline-primary" href="<?= route_url('pinboard.index', $identifier) ?>"><i class="fas fa-thumbtack me-2"></i><?= esc(ui_text('nav.pinboard')) ?></a>
                    <a class="btn btn-outline-primary" href="<?= route_url('notifications.index', $identifier) ?>"><i class="fas fa-bell me-2"></i><?= esc(ui_text('nav.notifications')) ?></a>
                    <?php if ($dashboardContext['can_view_reports']): ?>
                        <a class="btn btn-outline-primary" href="<?= route_url('reports.index', $identifier) ?>"><i class="fas fa-chart-line me-2"></i><?= esc(ui_text('nav.reports')) ?></a>
                    <?php endif; ?>
                    <?php if ($dashboardContext['can_manage_members']): ?>
                        <a class="btn btn-outline-secondary" href="<?= route_url('invitations.index', $identifier) ?>"><i class="fas fa-user-plus me-2"></i><?= esc(ui_text('nav.invitations')) ?></a>
                    <?php endif; ?>
                    <?php if ($dashboardContext['can_manage_settings']): ?>
                        <a class="btn btn-outline-secondary" href="<?= route_url('settings.index', $identifier) ?>"><i class="fas fa-cog me-2"></i><?= esc(ui_text('nav.settings')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-xl-5">
        <div class="card card-outline card-secondary h-100 shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-semibold"><i class="fas fa-stream me-2"></i><?= esc(ui_locale() === 'it' ? 'Attivita recente' : 'Recent activity') ?></h3>
            </div>
            <div class="card-body dashboard-feed-list">
                <?php if ($recentNotifications === []): ?>
                    <div class="callout callout-secondary mb-0">
                        <h5 class="mb-1"><?= esc(ui_text('dashboard.empty.notifications.title')) ?></h5>
                        <p class="mb-0"><?= esc(ui_text('dashboard.empty.notifications.message')) ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notification): ?>
                        <div class="dashboard-feed-item">
                            <div class="dashboard-feed-icon <?= empty($notification['read_at']) ? 'dashboard-feed-icon--unread' : '' ?>">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="dashboard-feed-copy">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <strong><?= esc((string) $notification['title']) ?></strong>
                                    <small class="text-muted"><?= esc((string) ($notification['created_at'] ?? '')) ?></small>
                                </div>
                                <?php if (! empty($notification['body'])): ?>
                                    <p class="mb-1 text-muted"><?= esc((string) $notification['body']) ?></p>
                                <?php endif; ?>
                                <span class="badge text-bg-light border"><?= esc(notification_type_label((string) $notification['type'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="row g-3 h-100">
            <div class="col-12 col-lg-6">
                <div class="card card-outline card-warning h-100 shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-semibold"><i class="fas fa-check-square me-2"></i><?= esc(ui_locale() === 'it' ? 'Prossime chore' : 'Upcoming chores') ?></h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($upcomingChores === []): ?>
                            <div class="p-3 text-muted"><?= esc(ui_locale() === 'it' ? 'Nessuna chore pending o overdue nei prossimi 10 giorni.' : 'No pending or overdue chores in the next 10 days.') ?></div>
                        <?php else: ?>
                            <ul class="products-list product-list-in-card ps-2 pe-2">
                                <?php foreach ($upcomingChores as $occurrence): ?>
                                    <li class="item">
                                        <div class="product-info ms-0">
                                            <a href="<?= route_url('chores.my', $identifier) ?>" class="product-title">
                                                <?= esc((string) $occurrence['chore_title']) ?>
                                                <span class="badge float-end <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                                            </a>
                                            <span class="product-description">
                                                <?= esc((string) ($occurrence['assigned_user_name'] ?? (ui_locale() === 'it' ? 'Non assegnata' : 'Unassigned'))) ?> / <?= esc((string) $occurrence['due_at']) ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card card-outline card-danger h-100 shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-semibold"><i class="fas fa-shopping-cart me-2"></i><?= esc(ui_locale() === 'it' ? 'Shopping urgente' : 'Urgent shopping') ?></h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($urgentShopping === []): ?>
                            <div class="p-3 text-muted"><?= esc(ui_locale() === 'it' ? 'Nessun item urgente non acquistato al momento.' : 'No urgent unpurchased items right now.') ?></div>
                        <?php else: ?>
                            <ul class="products-list product-list-in-card ps-2 pe-2">
                                <?php foreach ($urgentShopping as $item): ?>
                                    <li class="item">
                                        <div class="product-info ms-0">
                                            <a href="<?= route_url('shopping.show', $identifier, $item['shopping_list_id']) ?>" class="product-title">
                                                <?= esc((string) $item['name']) ?>
                                                <span class="badge float-end <?= esc((string) ($item['priority'] === 'urgent' ? 'badge--shopping-urgent' : 'badge--medium')) ?>">
                                                    <?= esc((string) ($item['priority'] === 'urgent'
                                                        ? (ui_locale() === 'it' ? 'Urgente' : 'Urgent')
                                                        : (ui_locale() === 'it' ? 'Media' : 'Medium'))) ?>
                                                </span>
                                            </a>
                                            <span class="product-description">
                                                <?= esc((string) $item['shopping_list_name']) ?> / <?= esc((string) ($item['assigned_user_name'] ?? (ui_locale() === 'it' ? 'Non assegnato' : 'Unassigned'))) ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card card-outline card-success shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title fw-semibold"><i class="fas fa-receipt me-2"></i><?= esc(ui_text('dashboard.section.latest_expenses')) ?></h3>
            </div>
            <div class="card-body table-responsive p-0">
                <?php if ($recentExpenses === []): ?>
                    <div class="p-3 text-muted"><?= esc(ui_text('dashboard.empty.expenses.message')) ?></div>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= esc(ui_locale() === 'it' ? 'Titolo' : 'Title') ?></th>
                                <th><?= esc(ui_locale() === 'it' ? 'Categoria' : 'Category') ?></th>
                                <th><?= esc(ui_locale() === 'it' ? 'Data' : 'Date') ?></th>
                                <th class="text-end"><?= esc(ui_locale() === 'it' ? 'Importo' : 'Amount') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentExpenses as $expense): ?>
                                <tr>
                                    <td><a href="<?= route_url('expenses.show', $identifier, $expense['id']) ?>"><?= esc((string) $expense['title']) ?></a></td>
                                    <td><?= esc((string) ($expense['category_name'] ?? ui_text('category.uncategorized'))) ?></td>
                                    <td><?= esc((string) $expense['expense_date']) ?></td>
                                    <td class="text-end"><?= esc(money_format((string) $expense['total_amount'], (string) $expense['currency'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title fw-semibold"><i class="fas fa-thumbtack me-2"></i><?= esc(ui_text('dashboard.section.pinboard')) ?></h3>
            </div>
            <div class="card-body p-0">
                <?php if ($recentPosts === []): ?>
                    <div class="p-3 text-muted"><?= esc(ui_text('dashboard.empty.posts.message')) ?></div>
                <?php else: ?>
                    <ul class="products-list product-list-in-card ps-2 pe-2">
                        <?php foreach ($recentPosts as $post): ?>
                            <li class="item">
                                <div class="product-info ms-0">
                                    <a href="<?= route_url('pinboard.show', $identifier, $post['id']) ?>" class="product-title">
                                        <?= esc((string) $post['title']) ?>
                                        <?php if ((int) ($post['is_pinned'] ?? 0) === 1): ?>
                                            <span class="badge float-end text-bg-warning"><?= esc(ui_locale() === 'it' ? 'In evidenza' : 'Pinned') ?></span>
                                        <?php endif; ?>
                                    </a>
                                            <span class="product-description">
                                                <?= esc((string) ($post['author_name'] ?? (ui_locale() === 'it' ? 'Sconosciuto' : 'Unknown'))) ?> / <?= esc((string) ($post['comments_count'] ?? 0)) ?> <?= esc(ui_locale() === 'it' ? 'commenti' : 'comments') ?>
                                            </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
