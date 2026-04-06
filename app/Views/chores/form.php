<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
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
        <p class="eyebrow"><?= $isEdit ? 'Edit Chore' : 'Create Chore' ?></p>
        <h1><?= $isEdit ? 'Aggiorna template faccenda' : 'Nuovo template faccenda' ?></h1>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>" data-recurring-form data-chore-form>
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span>Titolo</span>
                <input type="text" name="title" value="<?= esc(old('title', (string) ($chore['title'] ?? ''))) ?>" required>
            </label>
            <label class="field field--full">
                <span>Description</span>
                <textarea name="description" rows="4"><?= esc(old('description', (string) ($chore['description'] ?? ''))) ?></textarea>
            </label>
            <label class="field">
                <span>Punti</span>
                <input type="number" min="0" name="points" value="<?= esc(old('points', (string) ($chore['points'] ?? '0'))) ?>">
            </label>
            <label class="field">
                <span>Minuti stimati</span>
                <input type="number" min="0" name="estimated_minutes" value="<?= esc(old('estimated_minutes', (string) ($chore['estimated_minutes'] ?? '0'))) ?>">
            </label>
        </div>

        <div class="toggle-grid">
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?= old('is_active', (string) ($chore['is_active'] ?? '1')) ? 'checked' : '' ?>>
                <span>Template attivo</span>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="recurring_enabled" value="1" data-chore-recurring-toggle <?= old('recurring_enabled', ! empty($recurring) ? '1' : '') ? 'checked' : '' ?>>
                <span>Abilita recurring rule</span>
            </label>
        </div>

        <div class="form-grid">
            <label class="field">
                <span>Assignment mode</span>
                <select name="assignment_mode" data-chore-assignment-mode>
                    <option value="fixed" <?= old('assignment_mode', (string) ($chore['assignment_mode'] ?? 'fixed')) === 'fixed' ? 'selected' : '' ?>>Fixed</option>
                    <option value="rotation" <?= old('assignment_mode', (string) ($chore['assignment_mode'] ?? 'fixed')) === 'rotation' ? 'selected' : '' ?>>Rotation</option>
                </select>
            </label>
            <label class="field" data-chore-assignment-field="fixed">
                <span>Fixed assignee</span>
                <select name="fixed_assignee_user_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= old('fixed_assignee_user_id', (string) ($chore['fixed_assignee_user_id'] ?? '')) === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field" data-chore-assignment-field="rotation">
                <span>Rotation anchor</span>
                <select name="rotation_anchor_user_id">
                    <option value="">Primo membro attivo</option>
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
                <span>Frequency</span>
                <select name="frequency" data-recurring-frequency>
                    <option value="daily" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="custom" <?= old('frequency', (string) ($recurring['frequency'] ?? 'daily')) === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </label>
            <label class="field">
                <span>Interval</span>
                <input type="number" min="1" name="interval_value" value="<?= esc(old('interval_value', (string) ($recurring['interval_value'] ?? '1'))) ?>">
            </label>
            <label class="field">
                <span>Starts at</span>
                <input type="datetime-local" name="starts_at" value="<?= esc(old('starts_at', ! empty($recurring['starts_at']) ? date('Y-m-d\TH:i', strtotime((string) $recurring['starts_at'])) : '')) ?>">
            </label>
            <label class="field">
                <span>Ends at</span>
                <input type="datetime-local" name="ends_at" value="<?= esc(old('ends_at', ! empty($recurring['ends_at']) ? date('Y-m-d\TH:i', strtotime((string) $recurring['ends_at'])) : '')) ?>">
            </label>
            <label class="field" data-recurring-field="day_of_month">
                <span>Day of month</span>
                <input type="number" min="1" max="31" name="day_of_month" value="<?= esc(old('day_of_month', (string) ($recurring['day_of_month'] ?? ''))) ?>">
            </label>
            <label class="field" data-recurring-field="custom_unit">
                <span>Custom unit</span>
                <select name="custom_unit">
                    <option value="">Select</option>
                    <option value="day" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'day' ? 'selected' : '' ?>>Days</option>
                    <option value="week" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'week' ? 'selected' : '' ?>>Weeks</option>
                    <option value="month" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'month' ? 'selected' : '' ?>>Months</option>
                    <option value="year" <?= old('custom_unit', (string) ($recurring['custom_unit'] ?? '')) === 'year' ? 'selected' : '' ?>>Years</option>
                </select>
            </label>
            <div class="field field--full recurring-field--weekdays" data-recurring-field="by_weekday">
                <span>Weekdays</span>
                <div class="checkbox-grid">
                    <?php $selectedWeekdays = old('by_weekday', $recurring['by_weekday'] ?? []); ?>
                    <?php foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $weekday => $label): ?>
                        <label class="checkbox-row">
                            <input type="checkbox" name="by_weekday[]" value="<?= $weekday ?>" <?= in_array($weekday, array_map('intval', (array) $selectedWeekdays), true) ? 'checked' : '' ?>>
                            <span><?= esc($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-grid">
            <label class="field">
                <span>First manual occurrence</span>
                <input type="datetime-local" name="first_due_at" value="<?= esc(old('first_due_at', '')) ?>">
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Save changes' : 'Crea template' ?></button>
            <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
