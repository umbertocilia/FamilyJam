<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Role detail</p>
        <h1><?= esc((string) $roleDetailContext['role']['name']) ?></h1>
        <p class="hero__lead"><?= esc((string) ($roleDetailContext['role']['description'] ?? 'Ruolo household-scoped con grant list esplicita.')) ?></p>
        <div class="quick-filter-bar" aria-label="Meta ruolo">
            <span class="badge badge--expense-step"><?= esc((string) $roleDetailContext['role']['code']) ?></span>
            <span class="badge <?= ! empty($roleDetailContext['role']['is_system']) ? 'badge--expense-active' : 'badge--expense-edited' ?>">
                <?= ! empty($roleDetailContext['role']['is_system']) ? 'System role' : 'Custom role' ?>
            </span>
            <span class="badge <?= ! empty($roleDetailContext['role']['is_assignable']) ? 'badge--success' : 'badge--expense-deleted' ?>">
                <?= ! empty($roleDetailContext['role']['is_assignable']) ? 'Assegnabile' : 'Protetto' ?>
            </span>
        </div>
    </div>

    <div class="hero__actions">
        <?php if (empty($roleDetailContext['role']['is_system'])): ?>
            <a class="button button--primary" href="<?= route_url('roles.edit', $roleDetailContext['membership']['household_slug'], $roleDetailContext['role']['id']) ?>">Modifica ruolo</a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('roles.index', $roleDetailContext['membership']['household_slug']) ?>">Torna ai ruoli</a>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="detail-grid">
            <div class="detail-card"><span>Code</span><strong><?= esc((string) $roleDetailContext['role']['code']) ?></strong></div>
            <div class="detail-card"><span>Tipo</span><strong><?= ! empty($roleDetailContext['role']['is_system']) ? 'System role' : 'Custom role' ?></strong></div>
            <div class="detail-card"><span>Assegnabile</span><strong><?= ! empty($roleDetailContext['role']['is_assignable']) ? 'Si' : 'No' ?></strong></div>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Effective permissions</p>
                <h2>Permessi associati</h2>
            </div>
        </div>
        <?php if ($roleDetailContext['permissions'] === []): ?>
            <?php $title = 'Nessun permesso associato'; $message = 'Questo ruolo non espone ancora grant operativi.'; $actionLabel = null; $actionHref = null; ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <?php foreach ($roleDetailContext['permissions'] as $permission): ?>
                <div class="row-card">
                    <div>
                        <strong><?= esc((string) $permission['name']) ?></strong>
                        <p><?= esc((string) $permission['code']) ?></p>
                    </div>
                    <small><?= esc((string) $permission['module']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>
</section>
<?= $this->endSection() ?>
