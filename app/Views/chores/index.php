<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
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
        <p class="eyebrow"><?= esc(ui_text('nav.chores')) ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Routine di ' : 'Routines for ') ?><?= esc((string) $membership['household_name']) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it'
            ? 'Template, occorrenze, rotazioni e completamento rapido pensati per un utilizzo frequente da smartphone.'
            : 'Templates, occurrences, rotations and quick completion built for frequent smartphone use.') ?></p>
        <div class="quick-filter-bar" aria-label="<?= esc(ui_locale() === 'it' ? 'Navigazione faccende' : 'Chore navigation') ?>">
            <a class="module-chip" href="<?= route_url('chores.my', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Le mie faccende' : 'My chores') ?></a>
            <a class="module-chip" href="<?= route_url('chores.calendar', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Calendario' : 'Calendar') ?></a>
            <a class="module-chip" href="<?= route_url('chores.fairness', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Equita' : 'Fairness') ?></a>
        </div>
    </div>

    <div class="hero__actions">
        <?php if ($canManageChores): ?>
            <a class="button button--primary" href="<?= route_url('chores.create', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Nuova faccenda' : 'New chore') ?></a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('chores.my', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Le mie faccende' : 'My chores') ?></a>
        <a class="button button--secondary" href="<?= route_url('chores.calendar', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Calendario' : 'Calendar') ?></a>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Template attivi' : 'Active templates') ?></span><strong><?= esc((string) $summary['active_templates']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'In scadenza oggi' : 'Due today') ?></span><strong><?= esc((string) $summary['due_today']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Scadute' : 'Overdue') ?></span><strong><?= esc((string) $summary['overdue']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Le mie aperte' : 'My open chores') ?></span><strong><?= esc((string) $summary['my_open']) ?></strong></article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'In arrivo' : 'Upcoming') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Prossime faccende' : 'Upcoming chores') ?></h2>
            </div>
            <a class="button button--secondary" href="<?= route_url('chores.occurrences', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Tutte' : 'All') ?></a>
        </div>

        <div class="list-table">
            <?php if ($upcoming === []): ?>
                <?php
                $title = ui_locale() === 'it' ? 'Nessuna occorrenza in agenda' : 'No upcoming occurrences';
                $message = ui_locale() === 'it' ? 'Crea un template con regola ricorrente o genera manualmente una scadenza.' : 'Create a template with a recurring rule or generate a due date manually.';
                $actionLabel = $canManageChores ? (ui_locale() === 'it' ? 'Nuova faccenda' : 'New chore') : null;
                $actionHref = $canManageChores ? route_url('chores.create', $identifier) : null;
                ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($upcoming as $occurrence): ?>
                    <div class="list-table__row">
                        <div class="stack stack--compact">
                            <div class="expense-row__header">
                                <strong><?= esc((string) $occurrence['chore_title']) ?></strong>
                                <span class="badge <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                            </div>
                            <p><?= esc((string) $occurrence['due_at']) ?> · <?= esc((string) ($occurrence['assigned_user_name'] ?? (ui_locale() === 'it' ? 'Non assegnata' : 'Unassigned'))) ?></p>
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
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Equita' : 'Fairness') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Equilibrio della casa' : 'Household balance') ?></h2>
            </div>
            <a class="button button--secondary" href="<?= route_url('chores.fairness', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Dettagli' : 'Details') ?></a>
        </div>
        <?php if (! is_array($fairness) || $fairness['rows'] === []): ?>
            <?php
            $title = ui_locale() === 'it' ? 'Nessun dato disponibile' : 'No data available';
            $message = ui_locale() === 'it' ? 'La dashboard di equita si popola quando iniziano completamenti e skip reali.' : 'The fairness dashboard fills up once real completions and skips begin.';
            $actionLabel = ui_locale() === 'it' ? 'Apri agenda' : 'Open agenda';
            $actionHref = route_url('chores.calendar', $identifier);
            ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <div class="stack">
                <?php foreach (array_slice($fairness['rows'], 0, 4) as $row): ?>
                    <div class="fairness-row">
                        <div>
                            <strong><?= esc((string) ($row['display_name'] ?? $row['email'])) ?></strong>
                            <p><?= esc((string) $row['completed_count']) ?> <?= esc(ui_locale() === 'it' ? 'completate' : 'completed') ?> · <?= esc((string) $row['overdue_count']) ?> <?= esc(ui_locale() === 'it' ? 'scadute' : 'overdue') ?></p>
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
            <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Template' : 'Templates') ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Template attivi e pronti' : 'Active and ready templates') ?></h2>
        </div>
        <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>">
            <?= esc($canManageChores ? (ui_locale() === 'it' ? 'Apri template' : 'Open templates') : (ui_locale() === 'it' ? 'Vedi template' : 'View templates')) ?>
        </a>
    </div>
    <div class="list-table">
        <?php foreach (array_slice($templates, 0, 6) as $template): ?>
            <div class="list-table__row">
                <div class="stack stack--compact">
                    <div class="expense-row__header">
                        <strong><?= esc((string) $template['title']) ?></strong>
                        <span class="badge <?= (int) $template['is_active'] === 1 ? 'badge--expense-active' : 'badge--expense-deleted' ?>">
                            <?= esc((int) $template['is_active'] === 1 ? (ui_locale() === 'it' ? 'Attivo' : 'Active') : (ui_locale() === 'it' ? 'Disattivato' : 'Disabled')) ?>
                        </span>
                    </div>
                    <p><?= esc(chore_assignment_label((string) $template['assignment_mode'])) ?> · <?= esc((string) $template['estimated_minutes']) ?> min · <?= esc((string) $template['points']) ?> pts</p>
                </div>
                <div class="list-table__meta">
                    <?php if (is_array($template['recurring'] ?? null)): ?>
                        <span class="badge badge--expense-split"><?= esc(recurring_frequency_label((string) $template['recurring']['frequency'], $template['recurring']['custom_unit'] ?? null, (int) ($template['recurring']['interval_value'] ?? 1))) ?></span>
                    <?php else: ?>
                        <span class="badge badge--expense-step"><?= esc(ui_locale() === 'it' ? 'Solo manuale' : 'Manual only') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
