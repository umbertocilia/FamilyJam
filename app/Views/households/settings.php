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

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_text('settings.expense_groups')) ?></p>
            <h2><?= esc(ui_text('settings.expense_groups')) ?></h2>
        </div>
    </div>
    <p class="hero__lead"><?= esc(ui_text('settings.expense_groups.help')) ?></p>

    <div class="content-grid">
        <article class="panel panel--accent">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('common.details')) ?></p>
                    <h2><?= esc(ui_text('settings.expense_groups.create')) ?></h2>
                </div>
            </div>

            <form class="auth-form auth-form--compact" method="post" action="<?= route_url('settings.expense_groups.create', $householdContext['household']['slug']) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field">
                        <span><?= esc(ui_text('settings.expense_groups.name')) ?></span>
                        <input type="text" name="name" value="<?= esc((string) old('name', '')) ?>">
                    </label>
                    <label class="field">
                        <span><?= esc(ui_text('settings.expense_groups.description')) ?></span>
                        <input type="text" name="description" value="<?= esc((string) old('description', '')) ?>">
                    </label>
                    <label class="field">
                        <span><?= esc(ui_text('settings.expense_groups.color')) ?></span>
                        <input type="color" name="color" value="<?= esc((string) old('color', '#3c8dbc')) ?>">
                    </label>
                </div>
                <div class="field">
                    <span><?= esc(ui_text('settings.expense_groups.members')) ?></span>
                    <small class="field-hint"><?= esc(ui_text('settings.expense_groups.members_help')) ?></small>
                    <div class="summary-grid">
                        <?php $selectedMembers = array_map('strval', (array) (old('member_user_ids') ?? [])); ?>
                        <?php foreach ($availableMembers as $member): ?>
                            <label class="checkbox-row">
                                <input type="checkbox" name="member_user_ids[]" value="<?= esc((string) $member['user_id']) ?>" <?= in_array((string) $member['user_id'], $selectedMembers, true) ? 'checked' : '' ?>>
                                <span><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hero__actions">
                    <button class="button button--primary" type="submit"><?= esc(ui_text('settings.expense_groups.create')) ?></button>
                </div>
            </form>
        </article>

        <article class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('common.all')) ?></p>
                    <h2><?= esc(ui_text('settings.expense_groups')) ?></h2>
                </div>
            </div>

            <div class="list-table">
                <?php if (($householdContext['expenseGroups'] ?? []) === []): ?>
                    <?php $title = ui_text('settings.expense_groups.empty'); $message = ui_text('settings.expense_groups.help'); $actionLabel = null; $actionHref = null; ?>
                    <?= $this->include('partials/components/empty_state') ?>
                <?php else: ?>
                    <?php foreach ($householdContext['expenseGroups'] as $group): ?>
                        <div class="list-table__row">
                            <div class="stack stack--compact">
                                <div class="expense-row__meta">
                                    <span class="badge badge--expense-step" style="background: <?= esc((string) ($group['color'] ?? '#6c757d')) ?>22; color: <?= esc((string) ($group['color'] ?? '#6c757d')) ?>;">
                                        <?= esc(! empty($group['is_system']) ? ui_text('settings.expense_groups.system') : ui_text('settings.expense_groups.custom')) ?>
                                    </span>
                                </div>
                                <strong><?= esc((string) $group['name']) ?></strong>
                                <?php if (! empty($group['description'])): ?>
                                    <p><?= esc((string) $group['description']) ?></p>
                                <?php endif; ?>
                                <?php if (($group['members'] ?? []) !== []): ?>
                                    <p>
                                        <?php foreach ($group['members'] as $member): ?>
                                            <span class="badge badge--expense-step"><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="list-table__meta">
                                <?php if (! empty($group['is_system'])): ?>
                                    <small><?= esc(ui_text('settings.expense_groups.system_locked')) ?></small>
                                <?php else: ?>
                                    <form class="auth-form auth-form--compact" method="post" action="<?= route_url('settings.expense_groups.update', $householdContext['household']['slug'], $group['id']) ?>">
                                        <?= csrf_field() ?>
                                        <div class="form-grid">
                                            <input type="text" name="name" value="<?= esc((string) $group['name']) ?>">
                                            <input type="text" name="description" value="<?= esc((string) ($group['description'] ?? '')) ?>">
                                            <input type="color" name="color" value="<?= esc((string) ($group['color'] ?? '#3c8dbc')) ?>">
                                        </div>
                                        <div class="field">
                                            <span><?= esc(ui_text('settings.expense_groups.members')) ?></span>
                                            <div class="summary-grid">
                                                <?php foreach ($availableMembers as $member): ?>
                                                    <label class="checkbox-row">
                                                        <input
                                                            type="checkbox"
                                                            name="member_user_ids[]"
                                                            value="<?= esc((string) $member['user_id']) ?>"
                                                            <?= in_array((int) $member['user_id'], array_map(static fn ($value): int => (int) $value, $group['member_user_ids'] ?? []), true) ? 'checked' : '' ?>
                                                        >
                                                        <span><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="hero__actions">
                                            <button class="button button--secondary" type="submit"><?= esc(ui_text('settings.expense_groups.update')) ?></button>
                                        </div>
                                    </form>
                                    <form method="post" action="<?= route_url('settings.expense_groups.delete', $householdContext['household']['slug'], $group['id']) ?>">
                                        <?= csrf_field() ?>
                                        <button class="button button--secondary" type="submit"><?= esc(ui_text('settings.expense_groups.delete')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
<?= $this->endSection() ?>
