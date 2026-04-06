<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Preview</p>
        <h1><?= esc($preview['household']['name']) ?></h1>
        <p class="hero__lead"><?= esc($preview['household']['subtitle']) ?></p>
    </div>

    <div class="summary-grid">
        <?php foreach ($preview['summary'] as $item): ?>
            <article class="metric-card">
                <span><?= esc($item['label']) ?></span>
                <strong><?= esc($item['value']) ?></strong>
                <small><?= esc($item['trend']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel panel--dense">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow">Moduli</p>
            <h2>Panoramica applicazione</h2>
        </div>
    </div>

    <div class="pill-cluster">
        <?php foreach ($preview['quickActions'] as $action): ?>
            <span class="module-chip"><?= esc($action) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="hero__actions hero__actions--compact">
        <a class="button button--primary" href="<?= route_url('auth.login') ?>">Log in</a>
        <a class="button button--secondary" href="<?= route_url('auth.register') ?>">Register</a>
    </div>
</section>
<?= $this->endSection() ?>
