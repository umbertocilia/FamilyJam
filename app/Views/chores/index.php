<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $choreOverviewContext['membership'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$summary = $choreOverviewContext['summary'];
$templates = $choreOverviewContext['templates'];
$upcoming = $choreOverviewContext['upcomingOccurrences'];
$fairness = $choreOverviewContext['fairness'];
$canManageChores = ! empty($choreOverviewContext['canManageChores']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Chores</p>
        <h1>Routine di <?= esc((string) $membership['household_name']) ?></h1>
        <p class="hero__lead">Template, occorrenze, rotazioni e completion flow progettati per uso rapido da smartphone.</p>
        <div class="quick-filter-bar" aria-label="Navigazione chores">
            <a class="module-chip" href="<?= route_url('chores.my', $identifier) ?>">My chores</a>
            <a class="module-chip" href="<?= route_url('chores.calendar', $identifier) ?>">Calendar</a>
            <a class="module-chip" href="<?= route_url('chores.fairness', $identifier) ?>">Fairness</a>
        </div>
    </div>

    <div class="hero__actions">
        <?php if ($canManageChores): ?>
            <a class="button button--primary" href="<?= route_url('chores.create', $identifier) ?>">Nuovo chore</a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('chores.my', $identifier) ?>">My chores</a>
        <a class="button button--secondary" href="<?= route_url('chores.calendar', $identifier) ?>">Calendar</a>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span>Template attivi</span><strong><?= esc((string) $summary['active_templates']) ?></strong></article>
    <article class="metric-card"><span>Due oggi</span><strong><?= esc((string) $summary['due_today']) ?></strong></article>
    <article class="metric-card"><span>Overdue</span><strong><?= esc((string) $summary['overdue']) ?></strong></article>
    <article class="metric-card"><span>My open chores</span><strong><?= esc((string) $summary['my_open']) ?></strong></article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Upcoming</p>
                <h2>Upcoming chores</h2>
            </div>
            <a class="button button--secondary" href="<?= route_url('chores.occurrences', $identifier) ?>">All</a>
        </div>

        <div class="list-table">
            <?php if ($upcoming === []): ?>
                <?php $title = 'Nessuna occorrenza in agenda'; $message = 'Crea un template con recurring rule o genera manualmente una scadenza.'; $actionLabel = $canManageChores ? 'Nuovo chore' : null; $actionHref = $canManageChores ? route_url('chores.create', $identifier) : null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($upcoming as $occurrence): ?>
                    <div class="list-table__row">
                        <div class="stack stack--compact">
                            <div class="expense-row__header">
                                <strong><?= esc((string) $occurrence['chore_title']) ?></strong>
                                <span class="badge <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                            </div>
                            <p><?= esc((string) $occurrence['due_at']) ?> · <?= esc((string) ($occurrence['assigned_user_name'] ?? 'Unassigned')) ?></p>
                        </div>
                        <div class="list-table__meta">
                            <span class="badge badge--expense-step"><?= esc((string) ($occurrence['estimated_minutes'] ?? 0)) ?> min</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Fairness</p>
                <h2>Equilibrio household</h2>
            </div>
            <a class="button button--secondary" href="<?= route_url('chores.fairness', $identifier) ?>">Details</a>
        </div>
        <?php if (! is_array($fairness) || $fairness['rows'] === []): ?>
            <?php $title = 'Nessun dato disponibile'; $message = 'Il dashboard di equita si popola quando iniziano completamenti e skip reali.'; $actionLabel = 'Open agenda'; $actionHref = route_url('chores.calendar', $identifier); ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <div class="stack">
                <?php foreach (array_slice($fairness['rows'], 0, 4) as $row): ?>
                    <div class="fairness-row">
                        <div>
                            <strong><?= esc((string) ($row['display_name'] ?? $row['email'])) ?></strong>
                            <p><?= esc((string) $row['completed_count']) ?> completed · <?= esc((string) $row['overdue_count']) ?> overdue</p>
                        </div>
                        <span class="badge badge--expense-active"><?= esc((string) $row['points_total']) ?> pts</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow">Templates</p>
            <h2>Template attivi e pronti</h2>
        </div>
        <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>">
            <?= $canManageChores ? 'Open templates' : 'Vedi templates' ?>
        </a>
    </div>
    <div class="list-table">
        <?php foreach (array_slice($templates, 0, 6) as $template): ?>
            <div class="list-table__row">
                <div class="stack stack--compact">
                    <div class="expense-row__header">
                        <strong><?= esc((string) $template['title']) ?></strong>
                        <span class="badge <?= (int) $template['is_active'] === 1 ? 'badge--expense-active' : 'badge--expense-deleted' ?>">
                            <?= (int) $template['is_active'] === 1 ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>
                    <p><?= esc(chore_assignment_label((string) $template['assignment_mode'])) ?> · <?= esc((string) $template['estimated_minutes']) ?> min · <?= esc((string) $template['points']) ?> pts</p>
                </div>
                <div class="list-table__meta">
                    <?php if (is_array($template['recurring'] ?? null)): ?>
                        <span class="badge badge--expense-split"><?= esc(recurring_frequency_label((string) $template['recurring']['frequency'], $template['recurring']['custom_unit'] ?? null, (int) ($template['recurring']['interval_value'] ?? 1))) ?></span>
                    <?php else: ?>
                        <span class="badge badge--expense-step">Manual only</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
