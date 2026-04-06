<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $myChoreContext['membership'];
$occurrences = $myChoreContext['occurrences'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">My Chores</p>
        <h1>Le tue faccende</h1>
        <p class="hero__lead">Vista rapida ottimizzata per smartphone, focalizzata sulle azioni immediate.</p>
        <div class="quick-filter-bar" aria-label="Scorciatoie personali">
            <a class="module-chip" href="<?= route_url('chores.index', $identifier) ?>">Overview chores</a>
            <a class="module-chip" href="<?= route_url('chores.calendar', $identifier) ?>">Calendar</a>
        </div>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('chores.occurrences', $identifier) ?>">All le occorrenze</a>
    </div>
</section>

<section class="panel">
    <div class="list-table">
        <?php if ($occurrences === []): ?>
            <?php $title = 'Nessuna faccenda assegnata'; $message = 'Quando una rotazione o un template fixed ti assegna un task, lo trovi qui.'; $actionLabel = 'All le occorrenze'; $actionHref = route_url('chores.occurrences', $identifier); ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <?php foreach ($occurrences as $occurrence): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <div class="expense-row__header">
                            <strong><?= esc((string) $occurrence['chore_title']) ?></strong>
                            <span class="badge <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                        </div>
                        <p><?= esc((string) $occurrence['due_at']) ?> - <?= esc((string) $occurrence['estimated_minutes']) ?> min</p>
                    </div>
                    <div class="list-table__meta chore-actions">
                        <?php if (in_array((string) $occurrence['status'], ['pending', 'overdue'], true)): ?>
                            <form method="post" action="<?= route_url('chores.complete', $identifier, $occurrence['id']) ?>">
                                <?= csrf_field() ?>
                                <button class="button button--primary" type="submit">Complete</button>
                            </form>
                            <form class="chore-inline-form" method="post" action="<?= route_url('chores.skip', $identifier, $occurrence['id']) ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="skip_reason" placeholder="Motivazione skip" required>
                                <button class="button button--secondary" type="submit">Skip</button>
                            </form>
                        <?php else: ?>
                            <span class="badge badge--expense-step"><?= esc((string) $occurrence['points_awarded']) ?> pts</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
