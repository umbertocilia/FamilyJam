<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
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
        <p class="eyebrow">Calendar</p>
        <h1>Calendar prossime 2 settimane</h1>
        <p class="hero__lead">Vista calendario semplice, leggibile su smartphone, focalizzata sulle scadenze reali.</p>
    </div>
    <div class="hero__actions">
        <form method="get" action="<?= route_url('chores.calendar', $identifier) ?>">
            <input type="date" name="start" value="<?= esc($calendarStart->format('Y-m-d')) ?>">
            <button class="button button--secondary" type="submit">Aggiorna</button>
        </form>
    </div>
</section>

<section class="panel">
    <div class="agenda-list">
        <?php if ($grouped === []): ?>
            <div class="row-card row-card--vertical">
                <strong>Nessuna scadenza nel range selezionato</strong>
                <p>Amplia il range o genera nuove occorrenze dai template.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $day => $rows): ?>
                <article class="agenda-day">
                    <header class="agenda-day__header">
                        <strong><?= esc($day) ?></strong>
                        <span><?= esc((string) count($rows)) ?> task</span>
                    </header>
                    <div class="list-table">
                        <?php foreach ($rows as $row): ?>
                            <div class="list-table__row">
                                <div class="stack stack--compact">
                                    <strong><?= esc((string) $row['chore_title']) ?></strong>
                                    <p><?= esc(date('H:i', strtotime((string) $row['due_at']))) ?> · <?= esc((string) ($row['assigned_user_name'] ?? 'Unassigned')) ?></p>
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
