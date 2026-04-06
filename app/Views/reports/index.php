<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$household = $reportContext['household'];
$identifier = (string) ($household['slug'] ?? $reportContext['membership']['household_slug'] ?? $household['id']);
$expenseReport = $reportContext['expenseReport'];
$choreReport = $reportContext['choreReport'];
$expenseCategoryPreview = reset($expenseReport['byCategory']) ?: [];
$choreRowsPreview = array_slice($choreReport['byUser'], 0, 5);
$expenseMonthPreview = reset($expenseReport['byMonth']) ?: [];
$maxExpenseMonth = 0;
foreach ($expenseMonthPreview as $row) {
    $maxExpenseMonth = max($maxExpenseMonth, (int) ($row['amount_cents'] ?? 0));
}
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Panoramica report' : 'Reports overview') ?></p>
        <h1><?= esc((string) $household['name']) ?> <?= esc(ui_locale() === 'it' ? 'analytics' : 'analytics') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Panoramica di trend spese, distribuzione per gruppo e performance chore nella finestra attiva.' : 'Snapshot of expense trends, group distribution and chore performance across the active reporting window.') ?></p>
        <div class="quick-filter-bar" aria-label="Report navigation">
            <a class="module-chip" href="<?= route_url('reports.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
            <a class="module-chip" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_text('nav.expenses')) ?></a>
            <a class="module-chip" href="<?= route_url('reports.chores', $identifier) ?>"><?= esc(ui_text('nav.chores')) ?></a>
        </div>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Report spese' : 'Expense report') ?></a>
        <a class="button button--secondary" href="<?= route_url('reports.chores', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Report chore' : 'Chore report') ?></a>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Eventi spesa' : 'Expense events') ?></span>
        <strong><?= esc((string) $reportContext['summary']['expense_events']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Finestra filtro spese corrente' : 'Current expense filter window') ?></small>
    </article>
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Categorie tracciate' : 'Tracked groups') ?></span>
        <strong><?= esc((string) $reportContext['summary']['tracked_categories']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Distribuzione per gruppo e valuta' : 'Breakdown by group and currency') ?></small>
    </article>
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Chore completate' : 'Completed chores') ?></span>
        <strong><?= esc((string) $reportContext['summary']['chore_completed']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Range chore corrente' : 'Current chore range') ?></small>
    </article>
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Chore overdue' : 'Chores overdue') ?></span>
        <strong><?= esc((string) $reportContext['summary']['chore_overdue']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Richiedono attenzione' : 'Need attention') ?></small>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Anteprima spese' : 'Expense preview') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Top gruppi spesa' : 'Top expense groups') ?></h2>
            </div>
            <a class="module-chip" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_text('common.open')) ?></a>
        </div>

        <div class="stack-list">
            <?php if ($expenseCategoryPreview === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun dato spesa' : 'No expense data yet'; $message = ui_locale() === 'it' ? 'Il report si popola non appena il ledger contiene eventi.' : 'This report will populate as soon as the ledger contains events.'; $actionLabel = ui_locale() === 'it' ? 'Ledger spese' : 'Expense ledger'; $actionHref = route_url('expenses.index', $identifier); $icon = 'fas fa-chart-pie'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach (array_slice($expenseCategoryPreview, 0, 5) as $row): ?>
                    <div class="bar-chart__row">
                        <div class="row-card">
                            <div>
                                <strong><?= esc((string) $row['category_name']) ?></strong>
                                <p><?= esc((string) $row['expenses_count']) ?> <?= esc(ui_locale() === 'it' ? 'spese' : 'expenses') ?></p>
                            </div>
                            <span class="amount-pill"><?= esc(money_format((string) $row['amount'], (string) $row['currency'])) ?></span>
                        </div>
                        <div class="bar-chart__track">
                            <span class="bar-chart__fill" style="width: <?= esc(report_bar_width((int) $row['amount_cents'], (int) ($expenseCategoryPreview[0]['amount_cents'] ?? 0))) ?>"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Trend spese' : 'Expense trend') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Trend recente' : 'Recent trend') ?></h2>
            </div>
        </div>

        <div class="bar-chart">
            <?php if ($expenseMonthPreview === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun dato trend' : 'No trend data yet'; $message = ui_locale() === 'it' ? 'Servono piu spese nel periodo selezionato.' : 'You need more expenses in the selected period.'; $actionLabel = ui_locale() === 'it' ? 'Apri report spese' : 'Open expense report'; $actionHref = route_url('reports.expenses', $identifier); $icon = 'fas fa-chart-line'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach (array_slice($expenseMonthPreview, -6) as $row): ?>
                    <div class="bar-chart__row">
                        <div class="row-card">
                            <div>
                                <strong><?= esc(report_period_label((string) $row['period'])) ?></strong>
                                <p><?= esc((string) $row['expenses_count']) ?> expenses</p>
                            </div>
                            <span class="amount-pill"><?= esc(money_format((string) $row['amount'], (string) $row['currency'])) ?></span>
                        </div>
                        <div class="bar-chart__track">
                            <span class="bar-chart__fill" style="width: <?= esc(report_bar_width((int) $row['amount_cents'], $maxExpenseMonth)) ?>"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Anteprima chore' : 'Chore preview') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Equita per membro' : 'Fairness by member') ?></h2>
            </div>
            <a class="module-chip" href="<?= route_url('reports.chores', $identifier) ?>"><?= esc(ui_text('common.open')) ?></a>
        </div>

        <div class="list-table">
            <?php if ($choreRowsPreview === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun dato chore' : 'No chore data yet'; $message = ui_locale() === 'it' ? 'Le occorrenze completate e saltate appariranno qui.' : 'Completed and skipped occurrences will appear here.'; $actionLabel = ui_locale() === 'it' ? 'Apri fairness' : 'Open fairness'; $actionHref = route_url('chores.fairness', $identifier); $icon = 'fas fa-check-square'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($choreRowsPreview as $row): ?>
                    <div class="list-table__row">
                        <div>
                            <strong><?= esc((string) $row['display_name']) ?></strong>
                            <p><?= esc((string) $row['completed_count']) ?> completed / <?= esc((string) $row['skipped_count']) ?> skipped</p>
                        </div>
                        <div class="list-table__meta">
                            <strong><?= esc((string) $row['points_total']) ?> pt</strong>
                            <small><?= esc((string) $row['minutes_total']) ?> min</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Stato chore' : 'Chore status') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Breakdown del range' : 'Range breakdown') ?></h2>
            </div>
        </div>

        <div class="summary-grid">
            <?php foreach ($choreReport['statusBreakdown'] as $status => $count): ?>
                <div class="metric-card">
                    <span><?= esc(chore_status_label((string) $status)) ?></span>
                    <strong><?= esc((string) $count) ?></strong>
                    <small><?= esc((string) $choreReport['filters']['days']) ?> days</small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
