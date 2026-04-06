<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php helper('ui'); ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Risolutore casa attiva' : 'Current household resolver') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Seleziona la casa attiva' : 'Select the active household') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Questa schermata usa il resolver della casa attiva, il fallback sulle preferenze utente e l’elenco membership della casa.' : 'This screen uses the active household resolver, the user preference fallback and the household membership list.') ?></p>
    </div>
</section>

<section class="module-grid">
    <?php if ($households === []): ?>
        <article class="panel empty-state">
            <h2><?= esc(ui_locale() === 'it' ? 'Nessuna casa disponibile' : 'No household available') ?></h2>
            <p><?= esc(ui_locale() === 'it' ? 'Non ci sono ancora case disponibili per questo account.' : 'There are no households available for this account yet.') ?></p>
            <div class="hero__actions">
                <a class="button button--primary" href="<?= route_url('households.create') ?>"><?= esc(ui_locale() === 'it' ? 'Crea casa' : 'Create household') ?></a>
                <a class="button button--secondary" href="<?= route_url('households.index') ?>"><?= esc(ui_locale() === 'it' ? 'Torna alle case' : 'Back to households') ?></a>
            </div>
        </article>
    <?php else: ?>
        <?php foreach ($households as $household): ?>
            <article class="panel household-card">
                <div>
                    <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Membership casa' : 'Household membership') ?></p>
                    <h2><?= esc($household['household_name']) ?></h2>
                    <p><?= esc((string) ($household['role_codes'] ?? '')) ?></p>
                </div>
                <form method="post" action="<?= route_url('households.switch', $household['household_slug']) ?>">
                    <?= csrf_field() ?>
                    <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Apri dashboard' : 'Open dashboard') ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
