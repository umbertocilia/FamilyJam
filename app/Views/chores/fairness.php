<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $choreFairnessContext['membership'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$rows = $choreFairnessContext['rows'];
$totals = $choreFairnessContext['totals'];
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Equita' : 'Fairness dashboard') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Equita faccende' : 'Chore fairness') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Snapshot operativo per capire distribuzione reale di completamenti, punti e scadenze mancate.' : 'Operational snapshot to understand real completion, points and overdue distribution.') ?></p>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('chores.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Punti totali' : 'Total points') ?></span><strong><?= esc((string) $totals['points']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Completate' : 'Completed') ?></span><strong><?= esc((string) $totals['completed']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Scadute' : 'Overdue') ?></span><strong><?= esc((string) $totals['overdue']) ?></strong></article>
</section>

<section class="panel">
    <div class="list-table">
        <?php if ($rows === []): ?>
            <div class="row-card row-card--vertical">
                <strong><?= esc(ui_locale() === 'it' ? 'Nessun dato di equita disponibile' : 'No fairness data available') ?></strong>
                <p><?= esc(ui_locale() === 'it' ? 'La dashboard si attiva quando iniziano completamenti o skip reali.' : 'The dashboard becomes useful once real completions or skips start.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <strong><?= esc((string) ($row['display_name'] ?? $row['email'])) ?></strong>
                        <p><?= esc((string) $row['completed_count']) ?> <?= esc(ui_locale() === 'it' ? 'completate' : 'completed') ?> · <?= esc((string) $row['skipped_count']) ?> <?= esc(ui_locale() === 'it' ? 'saltate' : 'skipped') ?> · <?= esc((string) $row['overdue_count']) ?> <?= esc(ui_locale() === 'it' ? 'scadute' : 'overdue') ?></p>
                    </div>
                    <div class="list-table__meta">
                        <span class="badge badge--expense-active"><?= esc((string) $row['points_total']) ?> pts</span>
                        <small><?= esc((string) $row['completed_minutes']) ?> min <?= esc(ui_locale() === 'it' ? 'completati' : 'completed') ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
