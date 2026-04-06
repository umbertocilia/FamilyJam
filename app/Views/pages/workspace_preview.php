<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php if (! empty($isPreview)): ?>
    <section class="preview-banner panel panel--dense">
        <strong>Preview pubblico</strong>
        <p>Questa dashboard mostra la shell applicativa prima dell'implementazione completa dei moduli. Login e registrazione hanno gia un layout dedicato.</p>
        <div class="hero__actions">
            <a class="button button--primary" href="<?= route_url('auth.login') ?>">Log in</a>
            <a class="button button--secondary" href="<?= route_url('auth.register') ?>">Register</a>
        </div>
    </section>
<?php endif; ?>

<section class="workspace-hero panel panel--dense">
    <div>
        <p class="eyebrow">Preview workspace</p>
        <h1><?= esc($preview['household']['name']) ?></h1>
        <p class="hero__lead"><?= esc($preview['household']['subtitle']) ?></p>
    </div>

    <div class="workspace-hero__meta">
        <span><?= esc((string) $preview['household']['members']) ?> membri</span>
        <span><?= esc($preview['household']['currency']) ?></span>
    </div>
</section>

<section class="summary-grid">
    <?php foreach ($preview['summary'] as $item): ?>
        <article class="metric-card">
            <span><?= esc($item['label']) ?></span>
            <strong><?= esc($item['value']) ?></strong>
            <small><?= esc($item['trend']) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Balances</p>
                <h2>Balance overview</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($preview['balances'] as $balance): ?>
                <div class="row-card">
                    <div>
                        <strong><?= esc($balance['name']) ?></strong>
                        <p><?= esc($balance['status']) ?></p>
                    </div>
                    <span class="amount-pill"><?= esc($balance['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Chores</p>
                <h2>Faccende e rotazioni</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($preview['chores'] as $chore): ?>
                <div class="row-card">
                    <div>
                        <strong><?= esc($chore['title']) ?></strong>
                        <p><?= esc($chore['meta']) ?></p>
                    </div>
                    <span class="badge badge--<?= esc($chore['badge']) ?>"><?= esc($chore['badge']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Shopping</p>
                <h2>Lista della spesa</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($preview['shopping'] as $item): ?>
                <div class="row-card">
                    <div>
                        <strong><?= esc($item['name']) ?></strong>
                        <p><?= esc($item['meta']) ?></p>
                    </div>
                    <span class="badge badge--shopping-<?= esc($item['state']) ?>"><?= esc($item['state']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Pinboard</p>
                <h2>Bacheca condivisa</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($preview['pinboard'] as $pin): ?>
                <div class="row-card row-card--vertical">
                    <strong><?= esc($pin['title']) ?></strong>
                    <p><?= esc($pin['meta']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Notifications</p>
                <h2>Notification center</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($preview['notifications'] as $notification): ?>
                <div class="row-card row-card--vertical">
                    <strong><?= esc($notification['title']) ?></strong>
                    <p><?= esc($notification['detail']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Reports</p>
                <h2><?= esc($preview['report']['title']) ?></h2>
            </div>
        </div>

        <div class="bar-chart">
            <?php foreach ($preview['report']['bars'] as $bar): ?>
                <div class="bar-chart__row">
                    <span><?= esc($bar['label']) ?></span>
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" style="width: <?= esc((string) $bar['value']) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel panel--dense">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow">Quick actions</p>
            <h2>Azioni ricorrenti</h2>
        </div>
    </div>

    <div class="module-list">
        <?php foreach ($preview['quickActions'] as $action): ?>
            <span class="module-chip"><?= esc($action) ?></span>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
