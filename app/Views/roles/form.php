<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php $role = $roleFormContext['role'] ?? null; ?>
<?php $selectedPermissions = old('permission_codes') ?? $roleFormContext['selected_permissions']; ?>

<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Role editor</p>
        <h1><?= $formMode === 'edit' ? 'Modifica ruolo custom' : 'Crea ruolo custom' ?></h1>
        <p class="hero__lead">Il ruolo e limitato alla household corrente e puo essere assegnato a una o piu membership.</p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" action="<?= $formMode === 'edit'
        ? route_url('roles.update', $roleFormContext['membership']['household_slug'], $role['id'])
        : route_url('roles.store', $roleFormContext['membership']['household_slug']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-grid">
            <label class="field">
                <span>Nome</span>
                <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name', (string) ($role['name'] ?? ''))) ?>">
                <?php if (field_error($formErrors, 'name') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'name')) ?></small>
                <?php endif; ?>
            </label>

            <label class="field">
                <span>Code</span>
                <input class="<?= esc(field_error_class($formErrors, 'code')) ?>" type="text" name="code" value="<?= esc(old('code', (string) ($role['code'] ?? ''))) ?>">
                <small class="field-hint">Solo lettere minuscole, numeri e underscore.</small>
                <?php if (field_error($formErrors, 'code') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'code')) ?></small>
                <?php endif; ?>
            </label>

            <label class="field field--full">
                <span>Description</span>
                <textarea class="<?= esc(field_error_class($formErrors, 'description')) ?>" name="description" rows="4"><?= esc(old('description', (string) ($role['description'] ?? ''))) ?></textarea>
            </label>
        </div>

        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Permissions</p>
                <h2>Grant list</h2>
            </div>
        </div>

        <?php foreach ($roleFormContext['permission_catalog'] as $module => $permissions): ?>
            <div class="stack stack--compact">
                <strong><?= esc(ucfirst($module)) ?></strong>
                <div class="toggle-grid">
                    <?php foreach ($permissions as $permission): ?>
                        <label class="checkbox-row">
                            <input type="checkbox" name="permission_codes[]" value="<?= esc((string) $permission['code']) ?>" <?= in_array($permission['code'], (array) $selectedPermissions, true) ? 'checked' : '' ?>>
                            <span>
                                <strong><?= esc((string) $permission['name']) ?></strong>
                                <small><?= esc((string) $permission['description']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= $formMode === 'edit' ? 'Aggiorna ruolo' : 'Crea ruolo' ?></button>
            <a class="button button--secondary" href="<?= route_url('roles.index', $roleFormContext['membership']['household_slug']) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
