<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $myChoreContext['membership'];
$occurrences = $myChoreContext['occurrences'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Le mie faccende' : 'My chores') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Le tue faccende' : 'Your chores') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Vista rapida ottimizzata per smartphone, focalizzata sulle azioni immediate.' : 'Quick smartphone-friendly view focused on immediate actions.') ?></p>
        <div class="quick-filter-bar" aria-label="<?= esc(ui_locale() === 'it' ? 'Scorciatoie personali' : 'Personal shortcuts') ?>">
            <a class="module-chip" href="<?= route_url('chores.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica faccende' : 'Chores overview') ?></a>
            <a class="module-chip" href="<?= route_url('chores.calendar', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Calendario' : 'Calendar') ?></a>
        </div>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('chores.occurrences', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Tutte le occorrenze' : 'All occurrences') ?></a>
    </div>
</section>

<section class="panel">
    <div class="list-table">
        <?php if ($occurrences === []): ?>
            <?php
            $title = ui_locale() === 'it' ? 'Nessuna faccenda assegnata' : 'No assigned chores';
            $message = ui_locale() === 'it' ? 'Quando una rotazione o un template fisso ti assegna un task, lo trovi qui.' : 'When a rotation or fixed template assigns a task to you, it will appear here.';
            $actionLabel = ui_locale() === 'it' ? 'Tutte le occorrenze' : 'All occurrences';
            $actionHref = route_url('chores.occurrences', $identifier);
            ?>
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
                                <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Completa' : 'Complete') ?></button>
                            </form>
                            <form class="chore-inline-form" method="post" action="<?= route_url('chores.skip', $identifier, $occurrence['id']) ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="skip_reason" placeholder="<?= esc(ui_locale() === 'it' ? 'Motivo dello skip' : 'Skip reason') ?>" required>
                                <button class="button button--secondary" type="submit"><?= esc(ui_locale() === 'it' ? 'Salta' : 'Skip') ?></button>
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
