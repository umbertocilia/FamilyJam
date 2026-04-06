<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Households</p>
        <h1>Create a new workspace</h1>
        <p class="hero__lead">Creation provisions the tenant, owner membership, owner role and initial settings in one transaction.</p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" action="<?= route_url('households.store') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field">
                <span>Name</span>
                <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name')) ?>">
            </label>

            <label class="field">
                <span>Base currency</span>
                <input class="<?= esc(field_error_class($formErrors, 'base_currency')) ?>" type="text" name="base_currency" value="<?= esc(old('base_currency', 'EUR')) ?>" maxlength="3">
            </label>

            <label class="field">
                <span>Timezone</span>
                <input class="<?= esc(field_error_class($formErrors, 'timezone')) ?>" type="text" name="timezone" value="<?= esc(old('timezone', 'Europe/Rome')) ?>">
            </label>

            <label class="field">
                <span>Locale</span>
                <select name="locale">
                    <option value="it" <?= old('locale', 'it') === 'it' ? 'selected' : '' ?>>Italian</option>
                    <option value="en" <?= old('locale') === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </label>

            <label class="field field--full">
                <span>Description</span>
                <textarea class="<?= esc(field_error_class($formErrors, 'description')) ?>" name="description" rows="4"><?= esc(old('description')) ?></textarea>
            </label>

            <label class="field field--full">
                <span>Avatar path or URL</span>
                <input class="<?= esc(field_error_class($formErrors, 'avatar_path')) ?>" type="text" name="avatar_path" value="<?= esc(old('avatar_path')) ?>">
            </label>
        </div>

        <div class="toggle-grid">
            <label class="checkbox-row">
                <input type="checkbox" name="simplify_debts" value="1" <?= old_bool('simplify_debts', true) ? 'checked' : '' ?>>
                <span>Enable debt simplification</span>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="chore_scoring_enabled" value="1" <?= old_bool('chore_scoring_enabled', true) ? 'checked' : '' ?>>
                <span>Enable chore scoring</span>
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit">Create household</button>
            <a class="button button--secondary" href="<?= route_url('households.index') ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
