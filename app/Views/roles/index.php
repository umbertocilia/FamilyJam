<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Roles & permissions</p>
        <h1><?= esc($roleContext['membership']['household_name']) ?> roles</h1>
        <p class="hero__lead">System roles and household-specific custom roles with granular permissions.</p>
        <div class="quick-filter-bar" aria-label="Role shortcuts">
            <a class="module-chip" href="<?= route_url('roles.index', $roleContext['membership']['household_slug']) ?>">Roles</a>
            <a class="module-chip" href="<?= route_url('memberships.index', $roleContext['membership']['household_slug']) ?>">Members</a>
        </div>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('roles.create', $roleContext['membership']['household_slug']) ?>">New custom role</a>
        <a class="button button--secondary" href="<?= route_url('memberships.index', $roleContext['membership']['household_slug']) ?>">Open members</a>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Role catalog</p>
                <h2>Available roles</h2>
            </div>
        </div>

        <div class="list-table">
            <?php if ($roleContext['roles'] === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun ruolo disponibile' : 'No roles available'; $message = ui_locale() === 'it' ? 'I ruoli di sistema e i ruoli custom appariranno qui.' : 'System roles and custom roles will appear here.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-user-shield'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($roleContext['roles'] as $role): ?>
                    <a class="list-table__row" href="<?= route_url('roles.show', $roleContext['membership']['household_slug'], $role['id']) ?>">
                        <div>
                            <strong><?= esc((string) $role['name']) ?></strong>
                            <p><?= esc((string) $role['code']) ?> | <?= ! empty($role['is_system']) ? 'System' : 'Custom' ?></p>
                        </div>
                        <div class="list-table__meta">
                            <span><?= esc((string) count((array) ($role['permission_codes'] ?? []))) ?> permissions</span>
                            <small><?= esc((string) ($role['membership_count'] ?? 0)) ?> memberships</small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Permission map</p>
                <h2>Permission catalog</h2>
            </div>
        </div>

        <?php foreach ($roleContext['permissions'] as $module => $permissions): ?>
            <div class="stack stack--compact">
                <strong><?= esc(ucfirst($module)) ?></strong>
                <div class="module-list">
                    <?php foreach ($permissions as $permission): ?>
                        <span class="module-chip"><?= esc((string) $permission['code']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </article>
</section>
<?= $this->endSection() ?>
