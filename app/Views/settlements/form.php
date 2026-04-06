<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $settlementFormContext['membership'];
$household = $settlementFormContext['household'];
$members = $settlementFormContext['members'];
$expenseGroups = $settlementFormContext['expenseGroups'] ?? [];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
$prefill = $settlementPrefill ?? [];
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('nav.settlements')) ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Registra un rimborso manuale' : 'Record a manual settlement') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Il settlement riduce o azzera i saldi reali tra due membri. Puoi partire dai suggerimenti del ledger per chiudere subito un debito aperto.' : 'A settlement reduces or clears the real balance between two members. You can start from ledger suggestions to close an open debt immediately.') ?></p>
    </div>

    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('settlements.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Storico rimborsi' : 'Settlement history') ?></a>
        <a class="button button--secondary" href="<?= route_url('balances.pairwise', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?></a>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Modulo rimborso' : 'Settlement form') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Dati rimborso' : 'Settlement details') ?></h2>
            </div>
        </div>

        <form class="auth-form" method="post" action="<?= route_url('settlements.store', $identifier) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="section-stack">
                <section class="form-section">
                    <div class="form-section__header">
                        <div>
                            <h3><?= esc(ui_locale() === 'it' ? '1. Direzione del rimborso' : '1. Settlement direction') ?></h3>
                            <p class="inline-hint"><?= esc(ui_locale() === 'it' ? 'Indica chi paga e chi riceve. Il settlement riduce il saldo reale tra questi due membri.' : 'Choose who pays and who receives. The settlement reduces the real balance between these two members.') ?></p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span><?= esc(ui_locale() === 'it' ? 'Da' : 'From') ?></span>
                            <select class="<?= esc(field_error_class($formErrors, 'from_user_id')) ?>" name="from_user_id" required>
                                <option value=""><?= esc(ui_locale() === 'it' ? 'Seleziona membro' : 'Select member') ?></option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) old('from_user_id', (string) ($prefill['from_user_id'] ?? '')) === (string) $member['user_id'] ? 'selected' : '' ?>>
                                        <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="field">
                            <span><?= esc(ui_locale() === 'it' ? 'A' : 'To') ?></span>
                            <select class="<?= esc(field_error_class($formErrors, 'to_user_id')) ?>" name="to_user_id" required>
                                <option value=""><?= esc(ui_locale() === 'it' ? 'Seleziona membro' : 'Select member') ?></option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) old('to_user_id', (string) ($prefill['to_user_id'] ?? '')) === (string) $member['user_id'] ? 'selected' : '' ?>>
                                        <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="form-section">
                    <div class="form-section__header">
                        <div>
                            <h3><?= esc(ui_locale() === 'it' ? '2. Importo e contesto' : '2. Amount and context') ?></h3>
                            <p class="inline-hint"><?= esc(ui_locale() === 'it' ? 'Valuta e importo devono riflettere il bucket reale del ledger che vuoi compensare.' : 'Currency and amount should match the real ledger bucket you want to settle.') ?></p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span><?= esc(ui_locale() === 'it' ? 'Data' : 'Date') ?></span>
                            <input class="<?= esc(field_error_class($formErrors, 'settlement_date')) ?>" type="date" name="settlement_date" value="<?= esc(old('settlement_date', date('Y-m-d'))) ?>" required>
                        </label>

                        <label class="field">
                            <span><?= esc(ui_text('expense.label.currency')) ?></span>
                            <input class="<?= esc(field_error_class($formErrors, 'currency')) ?>" type="text" name="currency" value="<?= esc(old('currency', (string) ($prefill['currency'] ?? $household['base_currency']))) ?>" maxlength="3" required>
                        </label>

                        <label class="field">
                            <span><?= esc(ui_text('settlement.label.group')) ?></span>
                            <select class="<?= esc(field_error_class($formErrors, 'expense_group_id')) ?>" name="expense_group_id">
                                <option value=""><?= esc(ui_text('expense.label.group.general')) ?></option>
                                <?php foreach ($expenseGroups as $group): ?>
                                    <option value="<?= esc((string) $group['id']) ?>" <?= (string) old('expense_group_id', (string) ($prefill['expense_group_id'] ?? '')) === (string) $group['id'] ? 'selected' : '' ?>>
                                        <?= esc((string) $group['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="field">
                            <span><?= esc(ui_locale() === 'it' ? 'Importo' : 'Amount') ?></span>
                            <input class="<?= esc(field_error_class($formErrors, 'amount')) ?>" type="number" name="amount" value="<?= esc(old('amount', (string) ($prefill['amount'] ?? ''))) ?>" step="0.01" min="0.01" required>
                        </label>

                        <label class="field">
                            <span><?= esc(ui_locale() === 'it' ? 'Metodo pagamento' : 'Payment method') ?></span>
                            <input class="<?= esc(field_error_class($formErrors, 'payment_method')) ?>" type="text" name="payment_method" value="<?= esc(old('payment_method')) ?>" maxlength="32" placeholder="bank transfer, cash, card">
                        </label>

                        <label class="field field--full">
                            <span><?= esc(ui_locale() === 'it' ? 'Nota' : 'Note') ?></span>
                            <textarea class="<?= esc(field_error_class($formErrors, 'note')) ?>" name="note" rows="4"><?= esc(old('note')) ?></textarea>
                        </label>

                        <label class="field field--full">
                            <span><?= esc(ui_locale() === 'it' ? 'Allegato' : 'Attachment') ?></span>
                            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </label>
                    </div>
                </section>
            </div>

            <div class="hero__actions">
                <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Registra rimborso' : 'Record settlement') ?></button>
                <a class="button button--secondary" href="<?= route_url('settlements.index', $identifier) ?>"><?= esc(ui_text('common.cancel')) ?></a>
            </div>
        </form>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Riferimento' : 'Reference') ?></p>
                <h2><?= esc(ui_locale() === 'it' ? 'Suggerimenti disponibili' : 'Available suggestions') ?></h2>
            </div>
        </div>

        <div class="stack-list">
            <?php foreach ($settlementFormContext['pairwiseBalances'] as $currency => $rows): ?>
                <div class="row-card row-card--vertical">
                    <strong><?= esc(ui_locale() === 'it' ? 'Ledger reale' : 'Real ledger') ?> - <?= esc($currency) ?></strong>
                    <?php if ($rows === []): ?>
                        <p><?= esc(ui_locale() === 'it' ? 'Nessun saldo aperto per questa valuta.' : 'No open balances for this currency.') ?></p>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <p><?= esc((string) $row['from_user_name']) ?> <?= esc(ui_locale() === 'it' ? 'deve a' : 'owes') ?> <?= esc((string) $row['to_user_name']) ?> <?= esc(money_format((string) $row['amount'], $currency)) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php foreach ($settlementFormContext['simplifiedTransfers'] as $currency => $rows): ?>
                <div class="row-card row-card--vertical">
                    <strong><?= esc(ui_locale() === 'it' ? 'Semplificato' : 'Simplified') ?> - <?= esc($currency) ?></strong>
                    <?php if ($rows === []): ?>
                        <p><?= esc(ui_locale() === 'it' ? 'Nessun trasferimento suggerito.' : 'No suggested transfer.') ?></p>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <p><?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?> <?= esc(money_format((string) $row['amount'], $currency)) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
