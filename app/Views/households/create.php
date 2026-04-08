<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php helper('ui'); ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Household' : 'Household') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Crea un nuovo spazio casa' : 'Create a new household workspace') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it'
            ? 'La procedura crea in un solo passaggio household, membership owner, ruolo iniziale e impostazioni base. Campi e opzioni sono organizzati per ridurre errori e velocizzare il primo avvio.'
            : 'This flow provisions the household, owner membership, initial role and default settings in a single step. Fields and options are grouped to reduce errors and speed up first-time setup.') ?></p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" action="<?= route_url('households.store') ?>" method="post">
        <?= csrf_field() ?>

        <div class="section-stack">
            <section class="form-section">
                <div class="form-section__header">
                    <div>
                        <h2><?= esc(ui_locale() === 'it' ? '1. Identita dello spazio' : '1. Workspace identity') ?></h2>
                        <p class="inline-hint"><?= esc(ui_locale() === 'it'
                            ? 'Nome e descrizione aiutano i membri a distinguere rapidamente questa household dalle altre.'
                            : 'Name and description help members quickly recognize this household among the others.') ?></p>
                    </div>
                </div>

                <div class="form-grid">
                    <label class="field">
                        <span><?= esc(ui_locale() === 'it' ? 'Nome household' : 'Household name') ?></span>
                        <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name')) ?>" required>
                    </label>

                    <label class="field field--full">
                        <span><?= esc(ui_locale() === 'it' ? 'Descrizione' : 'Description') ?></span>
                        <textarea class="<?= esc(field_error_class($formErrors, 'description')) ?>" name="description" rows="4"><?= esc(old('description')) ?></textarea>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <div>
                        <h2><?= esc(ui_locale() === 'it' ? '2. Impostazioni base' : '2. Core settings') ?></h2>
                        <p class="inline-hint"><?= esc(ui_locale() === 'it'
                            ? 'Queste impostazioni vengono usate subito in saldi, report, date e localizzazione dell\'interfaccia.'
                            : 'These settings are used immediately for balances, reports, dates and interface localization.') ?></p>
                    </div>
                </div>

                <div class="form-grid">
                    <label class="field">
                        <span><?= esc(ui_locale() === 'it' ? 'Valuta base' : 'Base currency') ?></span>
                        <input class="<?= esc(field_error_class($formErrors, 'base_currency')) ?>" type="text" name="base_currency" value="<?= esc(old('base_currency', 'EUR')) ?>" maxlength="3" required>
                    </label>

                    <label class="field">
                        <span><?= esc(ui_locale() === 'it' ? 'Fuso orario' : 'Timezone') ?></span>
                        <input class="<?= esc(field_error_class($formErrors, 'timezone')) ?>" type="text" name="timezone" value="<?= esc(old('timezone', 'Europe/Rome')) ?>" required>
                    </label>

                    <label class="field">
                        <span><?= esc(ui_locale() === 'it' ? 'Lingua' : 'Locale') ?></span>
                        <select name="locale">
                            <option value="it" <?= old('locale', 'it') === 'it' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.it')) ?></option>
                            <option value="en" <?= old('locale') === 'en' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.en')) ?></option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header">
                    <div>
                        <h2><?= esc(ui_locale() === 'it' ? '3. Modalita operative' : '3. Operational modes') ?></h2>
                        <p class="inline-hint"><?= esc(ui_locale() === 'it'
                            ? 'Attiva subito le opzioni che influenzano saldi e punteggi faccende. Potrai cambiarle in seguito senza ricreare la household.'
                            : 'Enable the options that affect balances and chore scoring from day one. You can change them later without recreating the household.') ?></p>
                    </div>
                </div>

                <div class="toggle-grid">
                    <label class="checkbox-row">
                        <input type="checkbox" name="simplify_debts" value="1" <?= old_bool('simplify_debts', true) ? 'checked' : '' ?>>
                        <span>
                            <strong><?= esc(ui_locale() === 'it' ? 'Semplifica debiti' : 'Enable debt simplification') ?></strong>
                            <small><?= esc(ui_locale() === 'it'
                                ? 'Mostra suggerimenti di pareggio piu compatti, senza alterare il ledger reale.'
                                : 'Shows more compact settlement suggestions without changing the real ledger.') ?></small>
                        </span>
                    </label>

                    <label class="checkbox-row">
                        <input type="checkbox" name="chore_scoring_enabled" value="1" <?= old_bool('chore_scoring_enabled', true) ? 'checked' : '' ?>>
                        <span>
                            <strong><?= esc(ui_locale() === 'it' ? 'Punteggio faccende attivo' : 'Enable chore scoring') ?></strong>
                            <small><?= esc(ui_locale() === 'it'
                                ? 'Tiene traccia di punti e distribuzione del carico nelle faccende ricorrenti.'
                                : 'Tracks points and distribution fairness across recurring chores.') ?></small>
                        </span>
                    </label>
                </div>
            </section>
        </div>

        <div class="form-actions">
            <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Crea household' : 'Create household') ?></button>
            <a class="button button--secondary" href="<?= route_url('households.index') ?>"><?= esc(ui_text('common.cancel')) ?></a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
