<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $choreFairnessContext['membership'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$rows = $choreFairnessContext['rows'];
$totals = $choreFairnessContext['totals'];
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Fairness Dashboard</p>
        <h1>Equita chores</h1>
        <p class="hero__lead">Snapshot operativo per capire distribuzione reale di completamenti, punti e overdue.</p>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('chores.index', $identifier) ?>">Overview</a>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span>Punti totali</span><strong><?= esc((string) $totals['points']) ?></strong></article>
    <article class="metric-card"><span>Completed</span><strong><?= esc((string) $totals['completed']) ?></strong></article>
    <article class="metric-card"><span>Overdue</span><strong><?= esc((string) $totals['overdue']) ?></strong></article>
</section>

<section class="panel">
    <div class="list-table">
        <?php if ($rows === []): ?>
            <div class="row-card row-card--vertical">
                <strong>Nessun dato di equita disponibile</strong>
                <p>Il dashboard si attiva quando iniziano completamenti o skip reali.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <strong><?= esc((string) ($row['display_name'] ?? $row['email'])) ?></strong>
                        <p><?= esc((string) $row['completed_count']) ?> completed · <?= esc((string) $row['skipped_count']) ?> skipped · <?= esc((string) $row['overdue_count']) ?> overdue</p>
                    </div>
                    <div class="list-table__meta">
                        <span class="badge badge--expense-active"><?= esc((string) $row['points_total']) ?> pts</span>
                        <small><?= esc((string) $row['completed_minutes']) ?> min completed</small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
