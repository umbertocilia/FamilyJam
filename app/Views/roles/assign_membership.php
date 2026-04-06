<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Membership roles</p>
        <h1>Roles di <?= esc((string) ($assignmentContext['target_membership']['display_name'] ?? $assignmentContext['target_membership']['email'])) ?></h1>
        <p class="hero__lead">Assegna uno o piu ruoli alla membership nel tenant corrente.</p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" action="<?= route_url('membership.roles.update', $assignmentContext['membership']['household_slug'], $assignmentContext['target_membership']['id']) ?>" method="post">
        <?= csrf_field() ?>

        <?php if ($assignmentContext['locked_roles'] !== []): ?>
            <div class="stack stack--compact">
                <strong>Roles preservati automaticamente</strong>
                <div class="module-list">
                    <?php foreach ($assignmentContext['locked_roles'] as $role): ?>
                        <span class="module-chip"><?= esc((string) $role['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="toggle-grid">
            <?php foreach ($assignmentContext['assignable_roles'] as $role): ?>
                <label class="checkbox-row">
                    <input type="checkbox" name="role_ids[]" value="<?= esc((string) $role['id']) ?>" <?= in_array((int) $role['id'], $assignmentContext['selected_role_ids'], true) ? 'checked' : '' ?>>
                    <span>
                        <strong><?= esc((string) $role['name']) ?></strong>
                        <small><?= esc((string) $role['description']) ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit">Save assignment</button>
            <a class="button button--secondary" href="<?= route_url('memberships.show', $assignmentContext['membership']['household_slug'], $assignmentContext['target_membership']['id']) ?>">Torna alla membership</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
