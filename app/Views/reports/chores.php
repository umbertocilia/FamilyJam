<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$household = $reportContext['household'];
$identifier = (string) ($household['slug'] ?? $reportContext['membership']['household_slug'] ?? $household['id']);
$filters = $reportContext['filters'];
$timelineMax = 0;
foreach ($reportContext['timeline'] as $row) {
    $timelineMax = max($timelineMax, (int) $row['completed'] + (int) $row['skipped'] + (int) $row['overdue'] + (int) $row['pending']);
}
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Report chore' : 'Chore reports') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Equita e performance chore' : 'Chore fairness and performance') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Vista operativa su completate, skipped, overdue e punteggi nel periodo selezionato.' : 'Operational view of completed, skipped, overdue and score totals in the selected period.') ?></p>
        <div class="quick-filter-bar" aria-label="Chore report navigation">
            <a class="module-chip" href="<?= route_url('reports.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
            <a class="module-chip" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_text('nav.expenses')) ?></a>
            <a class="module-chip" href="<?= route_url('reports.chores', $identifier) ?>"><?= esc(ui_text('nav.chores')) ?></a>
        </div>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('reports.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
        <a class="button button--secondary" href="<?= route_url('chores.fairness', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Dashboard fairness' : 'Fairness dashboard') ?></a>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Filtri' : 'Filters') ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Range e assegnatario' : 'Range and assignee') ?></h2>
        </div>
    </div>

    <form class="auth-form auth-form--compact" method="get" action="<?= route_url('reports.chores', $identifier) ?>">
        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Periodo' : 'Period') ?></span>
                <select name="days">
                    <?php foreach ([7, 30, 90] as $days): ?>
                        <option value="<?= esc((string) $days) ?>" <?= (int) $filters['days'] === $days ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Ultimi ' . $days . ' giorni' : 'Last ' . $days . ' days') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Assegnatario' : 'Assignee') ?></span>
                <select name="assigned_user_id">
                    <option value=""><?= esc(ui_text('common.all')) ?></option>
                    <?php foreach ($reportContext['members'] as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) ($filters['assigned_user_id'] ?? '') === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Aggiorna report' : 'Refresh report') ?></button>
            <a class="button button--secondary" href="<?= route_url('reports.chores', $identifier) ?>">Reset</a>
        </div>
    </form>
</section>

<section class="summary-grid">
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Occorrenze' : 'Occurrences') ?></span>
        <strong><?= esc((string) $reportContext['summary']['occurrences_count']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Nel periodo' : 'In the selected period') ?></small>
    </article>
    <article class="metric-card">
        <span>Completed</span>
        <strong><?= esc((string) $reportContext['summary']['completed']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Task chiusi' : 'Closed tasks') ?></small>
    </article>
    <article class="metric-card">
        <span>Skipped</span>
        <strong><?= esc((string) $reportContext['summary']['skipped']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Task saltati' : 'Skipped tasks') ?></small>
    </article>
    <article class="metric-card">
        <span>Overdue</span>
        <strong><?= esc((string) $reportContext['summary']['overdue']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Da recuperare' : 'Needs recovery') ?></small>
    </article>
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Punti assegnati' : 'Awarded points') ?></span>
        <strong><?= esc((string) $reportContext['summary']['points_total']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Solo completate' : 'Completed only') ?></small>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Per utente' : 'By user') ?></p>
                <h2>Scoreboard</h2>
            </div>
        </div>

        <div class="list-table">
            <?php if ($reportContext['byUser'] === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun dato utenti' : 'No user data'; $message = ui_locale() === 'it' ? 'Le occorrenze del range selezionato sono vuote.' : 'There are no occurrences in the selected range.'; $actionLabel = ui_locale() === 'it' ? 'Apri fairness' : 'Open fairness'; $actionHref = route_url('chores.fairness', $identifier); ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($reportContext['byUser'] as $row): ?>
                    <div class="list-table__row">
                        <div>
                            <strong><?= esc((string) $row['display_name']) ?></strong>
                            <p><?= esc((string) $row['completed_count']) ?> completed / <?= esc((string) $row['overdue_count']) ?> overdue / <?= esc((string) $row['skipped_count']) ?> skipped</p>
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

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Mix status' : 'Status mix') ?></p>
                <h2>Breakdown</h2>
            </div>
        </div>

        <div class="summary-grid">
            <?php foreach ($reportContext['statusBreakdown'] as $status => $count): ?>
                <div class="metric-card">
                    <span><?= esc(chore_status_label((string) $status)) ?></span>
                    <strong><?= esc((string) $count) ?></strong>
                    <small><?= esc((string) $filters['days']) ?> <?= esc(ui_locale() === 'it' ? 'giorni' : 'days') ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Timeline</p>
                <h2><?= esc(ui_locale() === 'it' ? 'Andamento giornaliero' : 'Daily trend') ?></h2>
            </div>
        </div>

        <div class="bar-chart">
            <?php if ($reportContext['timeline'] === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun andamento disponibile' : 'No trend available'; $message = ui_locale() === 'it' ? 'Non ci sono occorrenze nel periodo selezionato.' : 'There are no occurrences in the selected period.'; $actionLabel = ui_locale() === 'it' ? 'Apri agenda' : 'Open agenda'; $actionHref = route_url('chores.calendar', $identifier); ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($reportContext['timeline'] as $row): ?>
                    <?php $dayTotal = (int) $row['completed'] + (int) $row['skipped'] + (int) $row['overdue'] + (int) $row['pending']; ?>
                    <div class="bar-chart__row">
                        <div class="row-card">
                            <div>
                                <strong><?= esc(report_date_label((string) $row['date'])) ?></strong>
                                <p><?= esc((string) $row['completed']) ?> completed / <?= esc((string) $dayTotal) ?> <?= esc(ui_locale() === 'it' ? 'totali' : 'total') ?></p>
                            </div>
                            <span class="amount-pill"><?= esc((string) $dayTotal) ?> <?= esc(ui_locale() === 'it' ? 'occ' : 'occ') ?></span>
                        </div>
                        <div class="bar-chart__track">
                            <span class="bar-chart__fill" style="width: <?= esc(report_bar_width($dayTotal, $timelineMax)) ?>"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Occorrenze recenti' : 'Recent occurrences') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Ultime occorrenze' : 'Latest occurrences') ?></h2>
            </div>
        </div>

        <div class="list-table">
            <?php if ($reportContext['recentOccurrences'] === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessuna occorrenza nel periodo' : 'No occurrences in range'; $message = ui_locale() === 'it' ? 'Prova ad allargare il range temporale.' : 'Try widening the time range.'; $actionLabel = ui_locale() === 'it' ? 'Le mie chore' : 'My chores'; $actionHref = route_url('chores.my', $identifier); ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($reportContext['recentOccurrences'] as $occurrence): ?>
                    <a class="list-table__row" href="<?= route_url('chores.my', $identifier) ?>">
                        <div>
                            <strong><?= esc((string) $occurrence['chore_title']) ?></strong>
                            <p><?= esc((string) ($occurrence['assigned_user_name'] ?? (ui_locale() === 'it' ? 'Da assegnare' : 'Unassigned'))) ?> / <?= esc((string) $occurrence['due_at']) ?></p>
                        </div>
                        <div class="list-table__meta">
                            <span class="badge <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                            <small><?= esc((string) ($occurrence['points_awarded'] ?? 0)) ?> pt</small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
