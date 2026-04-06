<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $settlementContext['membership'];
$household = $settlementContext['household'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('nav.settlements')) ?></p>
        <h1><?= esc((string) $household['name']) ?> <?= esc(ui_text('nav.settlements')) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'I rimborsi aggiornano il ledger reale, supportano pagamenti parziali e mantengono intatta la cronologia delle spese.' : 'Settlements update the real ledger, support partial repayments and preserve the underlying expense history.') ?></p>
        <div class="quick-filter-bar" aria-label="<?= esc(ui_locale() === 'it' ? 'Navigazione saldi' : 'Balance navigation') ?>">
            <a class="module-chip" href="<?= route_url('balances.overview', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica saldi' : 'Balance overview') ?></a>
            <a class="module-chip" href="<?= route_url('balances.pairwise', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?></a>
        </div>
    </div>

    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('balances.overview', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Torna ai saldi' : 'Back to balances') ?></a>
        <?php if ($settlementContext['canCreateSettlement']): ?>
            <a class="button button--primary" href="<?= route_url('settlements.create', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Nuovo rimborso' : 'New settlement') ?></a>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Registro rimborsi' : 'Settlement log') ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Attivita rimborsi' : 'Settlement activity') ?></h2>
        </div>
    </div>

    <div class="list-table">
        <?php if ($settlementContext['settlements'] === []): ?>
            <?php $title = ui_locale() === 'it' ? 'Nessun settlement registrato' : 'No settlements recorded'; $message = ui_locale() === 'it' ? 'Inizia con un rimborso parziale o completo tra i membri della household.' : 'Start with a partial or full repayment between household members.'; $actionLabel = $settlementContext['canCreateSettlement'] ? (ui_locale() === 'it' ? 'Nuovo settlement' : 'New settlement') : null; $actionHref = $settlementContext['canCreateSettlement'] ? route_url('settlements.create', $identifier) : null; $icon = 'fas fa-exchange-alt'; ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <?php foreach ($settlementContext['settlements'] as $settlement): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <strong><?= esc((string) ($settlement['from_user_name'] ?? (ui_locale() === 'it' ? 'Utente' : 'User'))) ?> -> <?= esc((string) ($settlement['to_user_name'] ?? (ui_locale() === 'it' ? 'Utente' : 'User'))) ?></strong>
                        <p>
                            <?= esc((string) $settlement['settlement_date']) ?>
                            - <?= esc((string) ($settlement['payment_method'] ?? (ui_locale() === 'it' ? 'Metodo non specificato' : 'Method not specified'))) ?>
                        </p>
                        <p><?= esc(ui_text('expense.label.group')) ?>: <?= esc((string) (($settlement['expense_group_name'] ?? '') !== '' ? $settlement['expense_group_name'] : ui_text('expense.label.group.general'))) ?></p>
                        <?php if (! empty($settlement['note'])): ?>
                            <p><?= esc((string) $settlement['note']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="list-table__meta">
                        <strong><?= esc(money_format((string) $settlement['amount'], (string) $settlement['currency'])) ?></strong>
                        <?php if (! empty($settlement['attachment_id'])): ?>
                            <a href="<?= route_url('settlements.attachment', $identifier, $settlement['id']) ?>"><?= esc(ui_locale() === 'it' ? 'Allegato' : 'Attachment') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
