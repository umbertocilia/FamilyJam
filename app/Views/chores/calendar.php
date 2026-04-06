<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $choreCalendarContext['membership'];
$agendaRows = $choreCalendarContext['agendaRows'];
$calendarStart = $choreCalendarContext['calendarStart'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$grouped = [];
foreach ($agendaRows as $row) {
    $grouped[date('Y-m-d', strtotime((string) $row['due_at']))][] = $row;
}
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Calendario' : 'Calendar') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Calendario prossime 2 settimane' : 'Next 2 weeks calendar') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Vista calendario semplice, leggibile su smartphone, focalizzata sulle scadenze reali.' : 'Simple calendar view, readable on smartphones, focused on real due dates.') ?></p>
    </div>
    <div class="hero__actions">
        <form method="get" action="<?= route_url('chores.calendar', $identifier) ?>">
            <input type="date" name="start" value="<?= esc($calendarStart->format('Y-m-d')) ?>">
            <button class="button button--secondary" type="submit"><?= esc(ui_locale() === 'it' ? 'Aggiorna' : 'Refresh') ?></button>
        </form>
    </div>
</section>

<section class="panel">
    <div class="agenda-list">
        <?php if ($grouped === []): ?>
            <div class="row-card row-card--vertical">
                <strong><?= esc(ui_locale() === 'it' ? 'Nessuna scadenza nel range selezionato' : 'No due dates in the selected range') ?></strong>
                <p><?= esc(ui_locale() === 'it' ? 'Amplia il range o genera nuove occorrenze dai template.' : 'Expand the range or generate new occurrences from templates.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $day => $rows): ?>
                <article class="agenda-day">
                    <header class="agenda-day__header">
                        <strong><?= esc($day) ?></strong>
                        <span><?= esc((string) count($rows)) ?> <?= esc(ui_locale() === 'it' ? 'attivita' : 'tasks') ?></span>
                    </header>
                    <div class="list-table">
                        <?php foreach ($rows as $row): ?>
                            <div class="list-table__row">
                                <div class="stack stack--compact">
                                    <strong><?= esc((string) $row['chore_title']) ?></strong>
                                    <p><?= esc(date('H:i', strtotime((string) $row['due_at']))) ?> · <?= esc((string) ($row['assigned_user_name'] ?? (ui_locale() === 'it' ? 'Non assegnata' : 'Unassigned'))) ?></p>
                                </div>
                                <div class="list-table__meta">
                                    <span class="badge <?= esc(chore_status_badge_class((string) $row['status'])) ?>"><?= esc(chore_status_label((string) $row['status'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
