<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php helper('ui'); ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Casa attiva' : 'Current household') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Cambia casa' : 'Switch household') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'La scelta aggiorna la sessione e la casa predefinita per gli accessi successivi.' : 'This choice updates the session and the default household for future visits.') ?></p>
    </div>
</section>

<section class="card-grid">
    <?php foreach ($households as $household): ?>
        <article class="panel household-card">
            <div class="stack">
                <h2><?= esc($household['household_name']) ?></h2>
                <p><?= esc((string) ($household['role_codes'] ?? '')) ?></p>
            </div>
            <form method="post" action="<?= route_url('households.switch', $household['household_slug']) ?>">
                <?= csrf_field() ?>
                <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Seleziona' : 'Select') ?></button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?= $this->endSection() ?>
