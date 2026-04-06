<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $choreFormContext['membership'];
$members = $choreFormContext['members'];
$chore = $choreFormContext['chore'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$action = $isEdit ? route_url('chores.update', $identifier, $chore['id']) : route_url('chores.store', $identifier);
$recurring = is_array($chore['recurring'] ?? null) ? $chore['recurring'] : [];
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc($isEdit ? (ui_locale() === 'it' ? 'Modifica faccenda' : 'Edit chore') : (ui_locale() === 'it' ? 'Crea faccenda' : 'Create chore')) ?></p>
        <h1><?= esc($isEdit ? (ui_locale() === 'it' ? 'Aggiorna template faccenda' : 'Update chore template') : (ui_locale() === 'it' ? 'Nuovo template faccenda' : 'New chore template')) ?></h1>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>" data-recurring-form data-chore-form>
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Titolo' : 'Title') ?></span>
                <input type="text" name="title" value="<?= esc(old('title', (string) ($chore['title'] ?? ''))) ?>" required>
            </label>
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Descrizione' : 'Description') ?></span>
                <textarea name="description" rows="4"><?= esc(old('description', (string) ($chore['description'] ?? ''))) ?></textarea>
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Punti' : 'Points') ?></span>
                <input type="number" min="0" name="points" value="<?= esc(old('points', (string) ($chore['points'] ?? '0'))) ?>">
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Minuti stimati' : 'Estimated minutes') ?></span>
                <input type="number" min="0" name="estimated_minutes" value="<?= esc(old('estimated_minutes', (string) ($chore['estimated_minutes'] ?? '0'))) ?>">
            </label>
        </div>

        <div class="toggle-grid">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= old('is_active', (string) ($chore['is_active'] ?? '1')) ? 'checked' : '' ?>>
                <span><?= esc(ui_locale() === 'it' ? 'Template attivo' : 'Active template') ?></span>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="recurring_enabled" value="1" data-chore-recurring-toggle <?= old('recurring_enabled', ! empty($recurring) ? '1' : '') ? 'checked' : '' ?>>
                <span><?= esc(ui_locale() === 'it' ? 'Abilita regola ricorrente' : 'Enable recurring rule') ?></span>
            </label>
        </div>

        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Modalita assegnazione' : 'Assignment mode') ?></span>
                <select name="assignment_mode" data-chore-assignment-mode>
                    <option value="fixed" <?= old('assignment_mode', (string) ($chore['assignment_mode'] ?? 'fixed')) === 'fixed' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Fissa' : 'Fixed') ?></option>
                    <option value="rotation" <?= old('assignment_mode', (string) ($chore['assignment_mode'] ?? 'fixed')) === 'rotation' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Rotazione' : 'Rotation') ?></option>
                </select>
            </label>
            <label class="field" data-chore-assignment-field="fixed">
                <span><?= esc(ui_locale() === 'it' ? 'Assegnatario fisso' : 'Fixed assignee') ?></span>
                <select name="fixed_assignee_user_id">
                    <option value=""><?= esc(ui_locale() === 'it' ? 'Non assegnata' : 'Unassigned') ?></option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= old('fixed_assignee_user_id', (string) ($chore['fixed_assignee_user_id'] ?? '')) === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field" data-chore-assignment-field="rotation">
                <span><?= esc(ui_locale() === 'it' ? 'Ancora rotazione' : 'Rotation anchor') ?></span>
                <select name="rotation_anchor_user_id">
                    <option value=""><?= esc(ui_locale() === 'it' ? 'Primo membro attivo' : 'First active member') ?></option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= old('rotation_anchor_user_id', (string) ($chore['rotation_anchor_user_id'] ?? '')) === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="form-grid" data-chore-recurring-fields>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Frequenza' : 'Frequency') ?></span>
                <select name="frequency" data-recurring-frequency>
                    <option value="daily" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'daily' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Giornaliera' : 'Daily') ?></option>
                    <option value="weekly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'weekly' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Settimanale' : 'Weekly') ?></option>
                    <option value="monthly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'monthly' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Mensile' : 'Monthly') ?></option>
                    <option value="yearly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'yearly' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Annuale' : 'Yearly') ?></option>
                    <option value="custom" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'custom' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Personalizzata' : 'Custom') ?></option>
                </select>
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Intervallo' : 'Interval') ?></span>
                <input type="number" min="1" name="interval_value" value="<?= esc(old('interval_value', (string) ($recurring['interval_value'] ?? '1'))) ?>">
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Inizio' : 'Starts at') ?></span>
                <input type="datetime-local" name="starts_at" value="<?= esc(old('starts_at', ! empty($recurring['starts_at']) ? date('Y-m-d\\TH:i', strtotime((string) $recurring['starts_at'])) : '')) ?>">
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Fine' : 'Ends at') ?></span>
                <input type="datetime-local" name="ends_at" value="<?= esc(old('ends_at', ! empty($recurring['ends_at']) ? date('Y-m-d\\TH:i', strtotime((string) $recurring['ends_at'])) : '')) ?>">
            </label>
            <label class="field" data-recurring-field="day_of_month">
                <span><?= esc(ui_locale() === 'it' ? 'Giorno del mese' : 'Day of month') ?></span>
                <input type="number" min="1" max="31" name="day_of_month" value="<?= esc(old('day_of_month', (string) ($recurring['day_of_month'] ?? ''))) ?>">
            </label>
            <label class="field" data-recurring-field="custom_unit">
                <span><?= esc(ui_locale() === 'it' ? 'Unita personalizzata' : 'Custom unit') ?></span>
                <select name="custom_unit">
                    <option value=""><?= esc(ui_locale() === 'it' ? 'Seleziona' : 'Select') ?></option>
                    <option value="day" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'day' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Giorni' : 'Days') ?></option>
                    <option value="week" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'week' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Settimane' : 'Weeks') ?></option>
                    <option value="month" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'month' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Mesi' : 'Months') ?></option>
                    <option value="year" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'year' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Anni' : 'Years') ?></option>
                </select>
            </label>
            <div class="field field--full recurring-field--weekdays" data-recurring-field="by_weekday">
                <span><?= esc(ui_locale() === 'it' ? 'Giorni della settimana' : 'Weekdays') ?></span>
                <div class="checkbox-grid">
                    <?php $selectedWeekdays = old('by_weekday', $recurring['by_weekday'] ?? []); ?>
                    <?php foreach ([1, 2, 3, 4, 5, 6, 7] as $weekday): ?>
                        <label class="checkbox-row">
                            <input type="checkbox" name="by_weekday[]" value="<?= $weekday ?>" <?= in_array($weekday, array_map('intval', (array) $selectedWeekdays), true) ? 'checked' : '' ?>>
                            <span><?= esc(weekday_label($weekday)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Prima occorrenza manuale' : 'First manual occurrence') ?></span>
                <input type="datetime-local" name="first_due_at" value="<?= esc(old('first_due_at', '')) ?>">
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc($isEdit ? (ui_locale() === 'it' ? 'Salva modifiche' : 'Save changes') : (ui_locale() === 'it' ? 'Crea template' : 'Create template')) ?></button>
            <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Annulla' : 'Cancel') ?></a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
