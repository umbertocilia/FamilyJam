<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Modulo futuro</p>
        <h1><?= esc($placeholder['title']) ?></h1>
        <p class="hero__lead"><?= esc($placeholder['summary']) ?></p>
        <div class="quick-filter-bar" aria-label="Stato modulo">
            <span class="badge badge--expense-step">Scaffold UI pronto</span>
            <span class="badge badge--expense-step">Tenant-aware</span>
            <span class="badge badge--expense-step">RBAC-ready</span>
        </div>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Milestone</p>
                <h2>Prossimo chunk</h2>
            </div>
        </div>
        <div class="table-like">
            <div class="table-like__row">
                <strong>Milestone operativa</strong>
                <p><?= esc($placeholder['milestone']) ?></p>
            </div>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Scope tecnico</p>
                <h2>Blocchi gia pronti</h2>
            </div>
        </div>
        <div class="module-list">
            <?php foreach ($placeholder['highlights'] as $highlight): ?>
                <span class="module-chip"><?= esc($highlight) ?></span>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel panel--dense">
    <div class="panel-toolbar">
        <div>
            <p class="section-heading__eyebrow">Stato attuale</p>
            <p class="page-note">Questa sezione eredita gia shell, tenant context, permessi e feedback states. Quando il modulo verra implementato, l'integrazione UI partira da qui.</p>
        </div>
        <div class="panel-toolbar__actions">
            <a class="button button--primary" href="<?= esc($placeholder['href'] ?? route_url('households.index')) ?>">Torna alla dashboard</a>
            <a class="button button--secondary" href="<?= route_url('households.switcher') ?>">Switch household</a>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
