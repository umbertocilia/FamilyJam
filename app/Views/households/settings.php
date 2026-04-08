<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php helper('ui'); ?>
<?php $availableMembers = $householdContext['availableMembers'] ?? []; ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('settings.title')) ?></p>
        <h1><?= esc($householdContext['household']['name']) ?></h1>
        <p class="hero__lead"><?= esc(ui_text('settings.lead')) ?></p>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Configurazione' : 'Configuration') ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Impostazioni predefinite' : 'Workspace defaults') ?></h2>
        </div>
    </div>
    <form class="auth-form" action="<?= route_url('settings.update', $householdContext['household']['slug']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Nome' : 'Name') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name', (string) $householdContext['household']['name'])) ?>">
            </label>

            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Valuta base' : 'Base currency') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'base_currency')) ?>" type="text" name="base_currency" value="<?= esc(old('base_currency', (string) $householdContext['household']['base_currency'])) ?>" maxlength="3">
            </label>

            <label class="field">
                <span><?= esc(ui_text('profile.timezone')) ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'timezone')) ?>" type="text" name="timezone" value="<?= esc(old('timezone', (string) $householdContext['household']['timezone'])) ?>">
            </label>

            <label class="field">
                <span><?= esc(ui_text('profile.locale')) ?></span>
                <select name="locale">
                    <?php $locale = old('locale', (string) ($householdContext['settings']['locale'] ?? 'it')); ?>
                    <option value="it" <?= $locale === 'it' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.it')) ?></option>
                    <option value="en" <?= $locale === 'en' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.en')) ?></option>
                </select>
            </label>

            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Descrizione' : 'Description') ?></span>
                <textarea name="description" rows="4"><?= esc(old('description', (string) ($householdContext['household']['description'] ?? ''))) ?></textarea>
            </label>

            <label class="field field--full">
                <span><?= esc(ui_text('profile.avatar')) ?></span>
                <input type="text" name="avatar_path" value="<?= esc(old('avatar_path', (string) ($householdContext['household']['avatar_path'] ?? ''))) ?>">
            </label>
        </div>

        <div class="toggle-grid">
            <label class="checkbox-row">
                <input type="checkbox" name="simplify_debts" value="1" <?= old_bool('simplify_debts', ! empty($householdContext['household']['simplify_debts'])) ? 'checked' : '' ?>>
                <span><?= esc(ui_locale() === 'it' ? 'Semplifica debiti' : 'Simplify debts') ?></span>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="chore_scoring_enabled" value="1" <?= old_bool('chore_scoring_enabled', ! empty($householdContext['household']['chore_scoring_enabled'])) ? 'checked' : '' ?>>
                <span><?= esc(ui_locale() === 'it' ? 'Punteggio chore attivo' : 'Chore scoring enabled') ?></span>
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Salva impostazioni' : 'Save settings') ?></button>
            <a class="button button--secondary" href="<?= route_url('households.dashboard', $householdContext['household']['slug']) ?>"><?= esc(ui_locale() === 'it' ? 'Torna alla dashboard' : 'Back to dashboard') ?></a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
