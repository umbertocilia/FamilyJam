<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
/** @var array<string, mixed> $expenseFormContext */
$membership = $expenseFormContext['membership'];
$expense = is_array($expenseFormContext['expense'] ?? null) ? $expenseFormContext['expense'] : [];
$categories = $expenseFormContext['categories'];
$expenseGroups = $expenseFormContext['expenseGroups'];
$members = $expenseFormContext['members'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$expenseId = (int) ($expense['id'] ?? 0);
$prefillExpenseGroupId = (string) ($prefillExpenseGroupId ?? '');
$activeCurrency = is_array($activeHousehold ?? null) ? (string) ($activeHousehold['base_currency'] ?? 'EUR') : 'EUR';
$currentCurrency = old('currency', (string) ($expense['currency'] ?? $activeCurrency));
$currentSplitMethod = old('split_method', (string) ($expense['split_method'] ?? 'equal'));
$payersByUser = [];
$splitsByUser = [];

foreach ($expenseFormContext['payers'] as $payer) {
    $payersByUser[(int) $payer['user_id']] = $payer;
}

foreach ($expenseFormContext['splits'] as $split) {
    $splitsByUser[(int) $split['user_id']] = $split;
}

$oldPayers = old('payers');
$oldSplits = old('splits');
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Spese' : 'Expenses') ?></p>
        <h1><?= esc($isEdit ? (ui_locale() === 'it' ? 'Modifica spesa' : 'Edit expense') : (ui_locale() === 'it' ? 'Nuova spesa condivisa' : 'New shared expense')) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it'
            ? 'Flusso guidato in quattro step: dettagli, pagatori, partecipanti e riepilogo finale. Le validazioni critiche restano sul server.'
            : 'Guided flow in four steps: details, payers, participants and final review. Critical validations remain on the server.') ?></p>
    </div>

    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('expenses.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Torna alla lista' : 'Back to list') ?></a>
        <?php if ($isEdit): ?>
            <a class="button button--secondary" href="<?= route_url('expenses.show', $identifier, $expenseId) ?>"><?= esc(ui_locale() === 'it' ? 'Apri dettaglio' : 'Open detail') ?></a>
        <?php endif; ?>
    </div>
</section>

<form
    class="stack"
    action="<?= $isEdit ? route_url('expenses.update', $identifier, $expenseId) : route_url('expenses.store', $identifier) ?>"
    method="post"
    enctype="multipart/form-data"
    data-expense-form
>
    <?= csrf_field() ?>

    <section class="panel expense-stepper-panel">
        <div class="expense-stepper" role="tablist" aria-label="Expense steps">
            <button class="expense-stepper__item is-active" type="button" data-expense-step-nav="details">
                <span class="expense-stepper__index">1</span>
                <span><?= esc(ui_text('expense.step.details')) ?></span>
            </button>
            <button class="expense-stepper__item" type="button" data-expense-step-nav="payers">
                <span class="expense-stepper__index">2</span>
                <span><?= esc(ui_text('expense.step.payers')) ?></span>
            </button>
            <button class="expense-stepper__item" type="button" data-expense-step-nav="splits">
                <span class="expense-stepper__index">3</span>
                <span><?= esc(ui_text('expense.step.splits')) ?></span>
            </button>
            <button class="expense-stepper__item" type="button" data-expense-step-nav="review">
                <span class="expense-stepper__index">4</span>
                <span><?= esc(ui_text('expense.step.review')) ?></span>
            </button>
        </div>
    </section>

    <section class="panel expense-form-section" data-expense-step="details" data-expense-step-index="0">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Step 1</p>
                <h2><?= esc(ui_text('expense.step.details')) ?></h2>
            </div>
            <span class="badge badge--expense-step">1 / 4</span>
        </div>
        <p class="field-hint"><?= esc(ui_locale() === 'it'
            ? 'Titolo, data, valuta e categoria definiscono l\'evento contabile. Il caricamento della ricevuta resta opzionale.'
            : 'Title, date, currency and category define the accounting event. Receipt upload stays optional.') ?></p>

        <div class="form-grid">
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Titolo' : 'Title') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'title')) ?>" type="text" name="title" value="<?= esc(old('title', (string) ($expense['title'] ?? ''))) ?>" maxlength="160" required>
            </label>

            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Data spesa' : 'Expense date') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'expense_date')) ?>" type="date" name="expense_date" value="<?= esc(old('expense_date', (string) ($expense['expense_date'] ?? date('Y-m-d')))) ?>" required>
            </label>

            <label class="field">
                <span><?= esc(ui_text('expense.label.currency')) ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'currency')) ?>" type="text" name="currency" value="<?= esc($currentCurrency) ?>" maxlength="3" required>
            </label>

            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Importo totale' : 'Total amount') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'total_amount')) ?>" type="number" name="total_amount" value="<?= esc(old('total_amount', (string) ($expense['total_amount'] ?? ''))) ?>" step="0.01" min="0.01" required data-expense-total>
            </label>

            <label class="field">
                <span><?= esc(ui_text('expense.label.category')) ?></span>
                <select class="<?= esc(field_error_class($formErrors, 'category_id')) ?>" name="category_id">
                    <option value=""><?= esc(ui_text('expense.label.uncategorized')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= esc((string) $category['id']) ?>" <?= (string) old('category_id', (string) ($expense['category_id'] ?? '')) === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span><?= esc(ui_text('expense.label.group')) ?></span>
                <select class="<?= esc(field_error_class($formErrors, 'expense_group_id')) ?>" name="expense_group_id">
                    <option value=""><?= esc(ui_text('expense.label.group.general')) ?></option>
                    <?php foreach ($expenseGroups as $group): ?>
                        <option value="<?= esc((string) $group['id']) ?>" <?= (string) old('expense_group_id', (string) ($expense['expense_group_id'] ?? $prefillExpenseGroupId)) === (string) $group['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $group['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="field-hint">
                    <?= esc(ui_locale() === 'it'
                        ? 'Puoi creare, aggiornare o cancellare i gruppi direttamente nella pagina Spese.'
                        : 'You can create, update or delete groups directly from the Expenses page.') ?>
                    <a href="<?= route_url('expenses.index', $identifier) ?>#expense-groups"><?= esc(ui_locale() === 'it' ? 'Apri gruppi spesa' : 'Open expense groups') ?></a>.
                </small>
            </label>

            <label class="field">
                <span><?= esc(ui_text('expense.label.split_method')) ?></span>
                <select class="<?= esc(field_error_class($formErrors, 'split_method')) ?>" name="split_method" data-split-method required>
                    <option value="equal" <?= $currentSplitMethod === 'equal' ? 'selected' : '' ?>><?= esc(ui_text('expense.method.equal')) ?></option>
                    <option value="exact" <?= $currentSplitMethod === 'exact' ? 'selected' : '' ?>><?= esc(ui_text('expense.method.exact')) ?></option>
                    <option value="percentage" <?= $currentSplitMethod === 'percentage' ? 'selected' : '' ?>><?= esc(ui_text('expense.method.percentage')) ?></option>
                    <option value="shares" <?= $currentSplitMethod === 'shares' ? 'selected' : '' ?>><?= esc(ui_text('expense.method.shares')) ?></option>
                </select>
            </label>

            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Descrizione / note' : 'Description / notes') ?></span>
                <textarea class="<?= esc(field_error_class($formErrors, 'description')) ?>" name="description" rows="4"><?= esc(old('description', (string) ($expense['description'] ?? ''))) ?></textarea>
            </label>

            <label class="field field--full">
                <span><?= esc(ui_text('expense.label.receipt')) ?></span>
                <input type="file" name="receipt_attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <?php if ($isEdit && ! empty($expense['receipt_attachment_id'])): ?>
                    <small class="field-hint"><?= esc(ui_text('expense.label.receipt.current', ['name' => (string) ($expense['receipt_original_name'] ?? (ui_locale() === 'it' ? 'allegato' : 'attachment'))])) ?></small>
                <?php endif; ?>
            </label>
        </div>

        <div class="form-actions">
            <button class="button button--primary" type="button" data-expense-step-next><?= esc(ui_text('expense.step.next')) ?></button>
        </div>
    </section>

    <section class="panel expense-form-section" data-expense-step="payers" data-expense-step-index="1" hidden>
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Step 2</p>
                <h2><?= esc(ui_text('expense.step.payers')) ?></h2>
            </div>
            <span class="badge badge--expense-step">2 / 4</span>
        </div>
        <p class="field-hint"><?= esc(ui_locale() === 'it'
            ? 'Attiva uno o piu pagatori. Gli importi vengono riequilibrati automaticamente quando possibile, ma la somma finale deve coincidere con il totale spesa.'
            : 'Enable one or more payers. Amounts are auto-balanced when possible, but the final sum must match the total expense.') ?></p>

        <div class="expense-form-grid">
            <?php foreach ($members as $member): ?>
                <?php
                $memberId = (int) $member['user_id'];
                $oldPayerRow = is_array($oldPayers) ? ($oldPayers[$memberId] ?? null) : null;
                $payerRow = $oldPayerRow ?? $payersByUser[$memberId] ?? null;
                $payerEnabled = $oldPayerRow !== null
                    ? ! empty($oldPayerRow['enabled'])
                    : ($payerRow !== null && (float) ($payerRow['amount_paid'] ?? 0) > 0);
                $payerAmount = $oldPayerRow['amount'] ?? $payerRow['amount_paid'] ?? '';
                ?>
                <article class="feature-card expense-member-card">
                    <div class="expense-member-card__header">
                        <div>
                            <h3><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></h3>
                            <p><?= esc((string) ($member['role_names'] ?? '')) ?></p>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="payers[<?= esc((string) $memberId) ?>][enabled]" value="1" <?= $payerEnabled ? 'checked' : '' ?> data-expense-payer-toggle>
                            <span><?= esc(ui_locale() === 'it' ? 'Paga' : 'Pays') ?></span>
                        </label>
                    </div>
                    <label class="field">
                        <span><?= esc(ui_locale() === 'it' ? 'Importo pagato' : 'Amount paid') ?></span>
                        <input type="number" name="payers[<?= esc((string) $memberId) ?>][amount]" value="<?= esc((string) $payerAmount) ?>" step="0.01" min="0" data-expense-payer-amount>
                    </label>
                    <small class="field-hint" data-expense-payer-preview><?= esc(ui_text('expense.preview.pays', ['amount' => '0.00 ' . $currentCurrency])) ?></small>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button class="button button--secondary" type="button" data-expense-step-back><?= esc(ui_text('expense.step.back')) ?></button>
            <button class="button button--primary" type="button" data-expense-step-next><?= esc(ui_text('expense.step.next')) ?></button>
        </div>
    </section>

    <section class="panel expense-form-section" data-expense-step="splits" data-expense-step-index="2" hidden>
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Step 3</p>
                <h2><?= esc(ui_text('expense.step.splits')) ?></h2>
            </div>
            <span class="badge badge--expense-step">3 / 4</span>
        </div>
        <p class="field-hint"><?= esc(ui_locale() === 'it'
            ? 'Seleziona i partecipanti e compila solo i campi del metodo di split attivo. Le percentuali restano manuali: puoi modificarle liberamente e il controllo finale verifica che totalizzino 100.'
            : 'Pick participants and fill only the inputs that belong to the active split method. Percentages stay manual: you can edit them freely and the final validation will check that they total 100.') ?></p>

        <div class="expense-form-grid">
            <?php foreach ($members as $member): ?>
                <?php
                $memberId = (int) $member['user_id'];
                $oldSplitRow = is_array($oldSplits) ? ($oldSplits[$memberId] ?? null) : null;
                $splitRow = $oldSplitRow ?? $splitsByUser[$memberId] ?? null;
                $splitEnabled = $oldSplitRow !== null
                    ? ! empty($oldSplitRow['enabled'])
                    : ($splitRow !== null && empty($splitRow['is_excluded']));
                ?>
                <article class="feature-card expense-member-card">
                    <div class="expense-member-card__header">
                        <div>
                            <h3><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></h3>
                            <p><?= esc((string) $member['email']) ?></p>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="splits[<?= esc((string) $memberId) ?>][enabled]" value="1" <?= $splitEnabled ? 'checked' : '' ?> data-expense-participant-toggle>
                            <span><?= esc(ui_locale() === 'it' ? 'Incluso' : 'Included') ?></span>
                        </label>
                    </div>

                    <div class="stack stack--compact">
                        <label class="field split-input split-input--exact" data-split-input="exact">
                            <span><?= esc(ui_locale() === 'it' ? 'Importo esatto' : 'Exact amount') ?></span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][owed_amount]" value="<?= esc((string) ($oldSplitRow['owed_amount'] ?? $splitRow['owed_amount'] ?? '')) ?>" step="0.01" min="0">
                        </label>

                        <label class="field split-input split-input--percentage" data-split-input="percentage">
                            <span><?= esc(ui_locale() === 'it' ? 'Percentuale' : 'Percentage') ?></span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][percentage]" value="<?= esc((string) ($oldSplitRow['percentage'] ?? $splitRow['percentage'] ?? '')) ?>" step="0.01" min="0" max="100">
                        </label>

                        <label class="field split-input split-input--shares" data-split-input="shares">
                            <span><?= esc(ui_locale() === 'it' ? 'Quote' : 'Shares') ?></span>
                            <input type="number" name="splits[<?= esc((string) $memberId) ?>][share_units]" value="<?= esc((string) ($oldSplitRow['share_units'] ?? $splitRow['share_units'] ?? '')) ?>" step="0.01" min="0">
                        </label>
                    </div>

                    <div class="expense-split-preview">
                        <span class="badge badge--expense-step" data-expense-split-label><?= esc(ui_text('expense.auto.equal')) ?></span>
                        <strong data-expense-split-preview><?= esc(ui_text('expense.preview.owes', ['amount' => '0.00 ' . $currentCurrency])) ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button class="button button--secondary" type="button" data-expense-step-back><?= esc(ui_text('expense.step.back')) ?></button>
            <button class="button button--primary" type="button" data-expense-step-next><?= esc(ui_text('expense.step.next')) ?></button>
        </div>
    </section>

    <section class="panel expense-form-section panel--accent" data-expense-step="review" data-expense-step-index="3" hidden>
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Step 4</p>
                <h2><?= esc(ui_text('expense.step.review')) ?></h2>
            </div>
            <span class="badge badge--expense-step">4 / 4</span>
        </div>

        <div class="summary-grid">
            <div class="metric-card">
                <span>Total</span>
                <strong data-expense-review-total><?= esc(money_format((string) old('total_amount', (string) ($expense['total_amount'] ?? '0.00')), $currentCurrency)) ?></strong>
                <small><?= esc(ui_locale() === 'it' ? 'Importo confermato dalle validazioni server-side' : 'Amount confirmed by server-side validation') ?></small>
            </div>
            <div class="metric-card">
                <span>Split method</span>
                <strong data-expense-review-method><?= esc(expense_split_label($currentSplitMethod)) ?></strong>
                <small><?= esc(ui_locale() === 'it' ? 'Mostrato anche nel dettaglio spesa' : 'Also shown on the expense detail page') ?></small>
            </div>
            <div class="metric-card">
                <span>Active payers</span>
                <strong data-expense-review-payers>0</strong>
                <small><?= esc(ui_locale() === 'it' ? 'Ne serve almeno uno' : 'At least one is required') ?></small>
            </div>
            <div class="metric-card">
                <span>Active participants</span>
                <strong data-expense-review-participants>0</strong>
                <small><?= esc(ui_locale() === 'it' ? 'Ne serve almeno uno' : 'At least one is required') ?></small>
            </div>
            <div class="metric-card">
                <span>Payer total</span>
                <strong data-expense-review-payer-total><?= esc(money_format('0.00', $currentCurrency)) ?></strong>
                <small><?= esc(ui_locale() === 'it' ? 'Deve combaciare con il totale spesa' : 'Must match the expense total') ?></small>
            </div>
            <div class="metric-card">
                <span>Split total</span>
                <strong data-expense-review-split-total><?= esc(money_format('0.00', $currentCurrency)) ?></strong>
                <small><?= esc(ui_locale() === 'it' ? 'Calcolato automaticamente dai partecipanti' : 'Calculated automatically from participants') ?></small>
            </div>
        </div>

        <div class="form-actions">
            <button class="button button--secondary" type="button" data-expense-step-back><?= esc(ui_text('expense.step.back')) ?></button>
            <button class="button button--primary" type="submit"><?= esc(ui_text($isEdit ? 'expense.step.submit.update' : 'expense.step.submit.create')) ?></button>
            <a class="button button--secondary" href="<?= route_url('expenses.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Annulla' : 'Cancel') ?></a>
        </div>
    </section>
</form>
<?= $this->endSection() ?>
