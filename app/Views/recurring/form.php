<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $recurringFormContext['membership'];
$household = $recurringFormContext['household'];
$rule = is_array($recurringFormContext['rule'] ?? null) ? $recurringFormContext['rule'] : [];
$template = is_array($rule['template'] ?? null) ? $rule['template'] : [];
$members = $recurringFormContext['members'];
$categories = $recurringFormContext['categories'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
$isEdit = $formMode === 'edit';
$ruleId = (int) ($rule['id'] ?? 0);
$payersByUser = [];
$splitsByUser = [];
foreach (($template['payers'] ?? []) as $payer) {
    $payersByUser[(int) $payer['user_id']] = $payer;
}
foreach (($template['splits'] ?? []) as $split) {
    $splitsByUser[(int) $split['user_id']] = $split;
}
$oldPayers = old('payers');
$oldSplits = old('splits');
$currentSplitMethod = old('split_method', (string) ($template['split_method'] ?? 'equal'));
$currentFrequency = old('frequency', (string) ($rule['frequency'] ?? 'monthly'));
$customUnit = old('custom_unit', (string) ($rule['schedule_config']['custom_unit'] ?? 'month'));
$weekdaySelection = old('by_weekday');
$weekdaySelection = is_array($weekdaySelection) ? array_map('intval', $weekdaySelection) : ($rule['by_weekday_list'] ?? []);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Recurring Expenses</p>
        <h1><?= $isEdit ? 'Modifica recurring expense' : 'Nuova recurring expense' ?></h1>
        <p class="hero__lead">Il template spesa viene validato come una expense reale, ma la data di occorrenza arriva dallo scheduler della regola.</p>
    </div>

    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('recurring.index', $identifier) ?>">Torna all’elenco</a>
        <a class="button button--secondary" href="<?= route_url('expenses.index', $identifier) ?>">Spese generate</a>
    </div>
</section>

<form
    class="stack"
    method="post"
    action="<?= $isEdit ? route_url('recurring.update', $identifier, $ruleId) : route_url('recurring.store', $identifier) ?>"
    data-recurring-form
>
    <?= csrf_field() ?>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Schedule</p>
                <h2>Regola temporale</h2>
            </div>
        </div>

        <div class="form-grid">
            <label class="field">
                <span>Frequency</span>
                <select name="frequency" data-recurring-frequency required>
                    <option value="daily" <?= $currentFrequency === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $currentFrequency === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $currentFrequency === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $currentFrequency === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    <option value="custom" <?= $currentFrequency === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </label>

            <label class="field">
                <span>Interval value</span>
                <input type="number" name="interval_value" value="<?= esc(old('interval_value', (string) ($rule['interval_value'] ?? '1'))) ?>" min="1" step="1" required>
            </label>

            <label class="field">
                <span>Starts at</span>
                <input type="datetime-local" name="starts_at" value="<?= esc(old('starts_at', isset($rule['starts_at']) ? str_replace(' ', 'T', substr((string) $rule['starts_at'], 0, 16)) : date('Y-m-d\T09:00'))) ?>" required>
            </label>

            <label class="field">
                <span>Ends at</span>
                <input type="datetime-local" name="ends_at" value="<?= esc(old('ends_at', isset($rule['ends_at']) && $rule['ends_at'] !== null ? str_replace(' ', 'T', substr((string) $rule['ends_at'], 0, 16)) : '')) ?>">
            </label>

            <label class="field recurring-field" data-recurring-field="day_of_month">
                <span>Day of month</span>
                <input type="number" name="day_of_month" value="<?= esc(old('day_of_month', (string) ($rule['day_of_month'] ?? ''))) ?>" min="1" max="31">
            </label>

            <label class="field recurring-field" data-recurring-field="custom_unit">
                <span>Custom unit</span>
                <select name="custom_unit">
                    <option value="day" <?= $customUnit === 'day' ? 'selected' : '' ?>>Days</option>
                    <option value="week" <?= $customUnit === 'week' ? 'selected' : '' ?>>Weeks</option>
                    <option value="month" <?= $customUnit === 'month' ? 'selected' : '' ?>>Months</option>
                    <option value="year" <?= $customUnit === 'year' ? 'selected' : '' ?>>Years</option>
                </select>
            </label>
        </div>

        <div class="recurring-field recurring-field--weekdays" data-recurring-field="by_weekday">
            <p class="field-hint">Weekdays</p>
            <div class="toggle-grid">
                <?php foreach ([1, 2, 3, 4, 5, 6, 7] as $weekday): ?>
                    <label class="checkbox-row">
                        <input type="checkbox" name="by_weekday[]" value="<?= $weekday ?>" <?= in_array($weekday, $weekdaySelection, true) ? 'checked' : '' ?>>
                        <span><?= esc(weekday_label($weekday)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Expense template</p>
                <h2>Dettagli spesa</h2>
            </div>
        </div>

        <div class="form-grid">
            <label class="field field--full">
                <span>Titolo</span>
                <input type="text" name="title" value="<?= esc(old('title', (string) ($template['title'] ?? ''))) ?>" maxlength="160" required>
            </label>

            <label class="field">
                <span>Valuta</span>
                <input type="text" name="currency" value="<?= esc(old('currency', (string) ($template['currency'] ?? $household['base_currency']))) ?>" maxlength="3" required>
            </label>

            <label class="field">
                <span>Totale</span>
                <input type="number" name="total_amount" value="<?= esc(old('total_amount', (string) ($template['total_amount'] ?? ''))) ?>" step="0.01" min="0.01" required>
            </label>

            <label class="field">
                <span>Categoria</span>
                <select name="category_id">
                    <option value="">Uncategorized</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= esc((string) $category['id']) ?>" <?= (string) old('category_id', (string) ($template['category_id'] ?? '')) === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span>Split method</span>
                <select name="split_method" data-split-method required>
                    <option value="equal" <?= $currentSplitMethod === 'equal' ? 'selected' : '' ?>>Equal</option>
                    <option value="exact" <?= $currentSplitMethod === 'exact' ? 'selected' : '' ?>>Exact amounts</option>
                    <option value="percentage" <?= $currentSplitMethod === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    <option value="shares" <?= $currentSplitMethod === 'shares' ? 'selected' : '' ?>>Shares</option>
                </select>
            </label>

            <label class="field field--full">
                <span>Description</span>
                <textarea name="description" rows="4"><?= esc(old('description', (string) ($template['description'] ?? ''))) ?></textarea>
            </label>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Payers</p>
                <h2>Pagatori del template</h2>
            </div>
        </div>

        <div class="expense-form-grid">
            <?php foreach ($members as $member): ?>
                <?php
                $memberId = (int) $member['user_id'];
                $oldPayerRow = is_array($oldPayers) ? ($oldPayers[$memberId] ?? null) : null;
                $payerRow = $oldPayerRow ?? $payersByUser[$memberId] ?? null;
                $payerEnabled = $oldPayerRow !== null ? ! empty($oldPayerRow['enabled']) : ($payerRow !== null && (float) ($payerRow['amount_paid'] ?? 0) > 0);
                $payerAmount = $oldPayerRow['amount'] ?? $payerRow['amount_paid'] ?? '';
                ?>
                <article class="feature-card expense-member-card">
                    <div class="expense-member-card__header">
                        <div>
                            <h3><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></h3>
                            <p><?= esc((string) $member['email']) ?></p>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="payers[<?= esc((string) $memberId) ?>][enabled]" value="1" <?= $payerEnabled ? 'checked' : '' ?> data-expense-payer-toggle>
                            <span>Paga</span>
                        </label>
                    </div>
                    <label class="field">
                        <span>Importo pagato</span>
                        <input type="number" name="payers[<?= esc((string) $memberId) ?>][amount]" value="<?= esc((string) $payerAmount) ?>" step="0.01" min="0">
                    </label>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Participants</p>
                <h2>Partecipanti del template</h2>
            </div>
        </div>

        <div class="expense-form-grid">
            <?php foreach ($members as $member): ?>
                <?php
                $memberId = (int) $member['user_id'];
                $oldSplitRow = is_array($oldSplits) ? ($oldSplits[$memberId] ?? null) : null;
                $splitRow = $oldSplitRow ?? $splitsByUser[$memberId] ?? null;
                $splitEnabled = $oldSplitRow !== null ? ! empty($oldSplitRow['enabled']) : ($splitRow !== null && empty($splitRow['is_excluded']));
                ?>
                <article class="feature-card expense-member-card">
                    <div class="expense-member-card__header">
                        <div>
                            <h3><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></h3>
                            <p><?= esc((string) $member['email']) ?></p>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="splits[<?= esc((string) $memberId) ?>][enabled]" value="1" <?= $splitEnabled ? 'checked' : '' ?> data-expense-participant-toggle>
                            <span>Partecipa</span>
                        </label>
                    </div>
                    <div class="stack stack--compact">
                        <label class="field split-input" data-split-input="exact">
                            <span>Quota exact</span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][owed_amount]" value="<?= esc((string) ($oldSplitRow['owed_amount'] ?? $splitRow['owed_amount'] ?? '')) ?>" step="0.01" min="0">
                        </label>
                        <label class="field split-input" data-split-input="percentage">
                            <span>Percentuale</span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][percentage]" value="<?= esc((string) ($oldSplitRow['percentage'] ?? $splitRow['percentage'] ?? '')) ?>" step="0.01" min="0" max="100">
                        </label>
                        <label class="field split-input" data-split-input="shares">
                            <span>Shares</span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][share_units]" value="<?= esc((string) ($oldSplitRow['share_units'] ?? $splitRow['share_units'] ?? '')) ?>" step="0.01" min="0">
                        </label>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel panel--accent">
        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Salva recurring expense' : 'Crea recurring expense' ?></button>
            <a class="button button--secondary" href="<?= route_url('recurring.index', $identifier) ?>">Cancel</a>
        </div>
    </section>
</form>
<?= $this->endSection() ?>
