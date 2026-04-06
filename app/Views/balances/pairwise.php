<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $balanceContext['membership'];
$household = $balanceContext['household'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?></p>
        <h1><?= esc((string) $household['name']) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Confronto tra pairwise balances reali del ledger e trasferimenti semplificati opzionali.' : 'Comparison between real pairwise balances and optional simplified transfers.') ?></p>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('balances.overview', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica saldi' : 'Balance overview') ?></a>
        <?php if (has_permission('add_settlement', $activeHousehold, $currentUserId)): ?>
            <a class="button button--secondary" href="<?= route_url('settlements.create', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Registra rimborso' : 'Record settlement') ?></a>
        <?php endif; ?>
    </div>
</section>

<?php if ($balanceContext['pairwiseBalances'] === []): ?>
    <section class="panel">
        <div class="row-card row-card--vertical">
            <strong><?= esc(ui_locale() === 'it' ? 'Nessun debito aperto' : 'No open debt') ?></strong>
            <p><?= esc(ui_locale() === 'it' ? 'Il ledger della household non contiene ancora esposizioni pairwise da mostrare.' : 'The household ledger does not contain pairwise exposures yet.') ?></p>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($balanceContext['pairwiseBalances'] as $currency => $rows): ?>
    <section class="content-grid">
        <article class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Ledger reale' : 'Real ledger') ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>

            <div class="stack-list">
                <?php if ($rows === []): ?>
                    <div class="row-card row-card--vertical">
                        <strong><?= esc(ui_locale() === 'it' ? 'Nessun debito aperto' : 'No open debt') ?></strong>
                        <p><?= esc(ui_locale() === 'it' ? 'Il ledger reale e gia in equilibrio per questa valuta.' : 'The real ledger is already balanced for this currency.') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="row-card">
                            <div>
                                <strong><?= esc((string) $row['from_user_name']) ?> <?= esc(ui_locale() === 'it' ? 'deve a' : 'owes') ?> <?= esc((string) $row['to_user_name']) ?></strong>
                                <p><?= esc(ui_locale() === 'it' ? 'Saldo reale calcolato dagli eventi senza semplificazione globale.' : 'Real balance computed from recorded events without global simplification.') ?></p>
                            </div>
                            <div class="list-table__meta">
                                <span class="amount-pill"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                                <?php if (has_permission('add_settlement', $activeHousehold, $currentUserId)): ?>
                                    <a class="button button--secondary" href="<?= route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) ?>">
                                        <?= esc(ui_locale() === 'it' ? 'Pareggia' : 'Settle') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel panel--accent">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Semplificato' : 'Simplified') ?></p>
                    <h2><?= esc(ui_locale() === 'it' ? 'Suggerimenti' : 'Suggestions') ?></h2>
                </div>
            </div>

            <p><?= esc(! empty($household['simplify_debts']) ? (ui_locale() === 'it' ? 'La household ha attivato la semplificazione debiti: sotto trovi i suggerimenti operativi.' : 'Debt simplification is enabled: below you will find operational suggestions.') : (ui_locale() === 'it' ? 'La semplificazione debiti non e attiva: il ledger reale resta la fonte principale.' : 'Debt simplification is disabled: the real ledger remains the main source.')) ?></p>

            <div class="stack-list">
                <?php $rows = $balanceContext['simplifiedTransfers'][$currency] ?? []; ?>
                <?php if ($rows === []): ?>
                    <div class="row-card row-card--vertical">
                        <strong><?= esc(ui_locale() === 'it' ? 'Nessun trasferimento suggerito' : 'No suggested transfer') ?></strong>
                        <p><?= esc(ui_locale() === 'it' ? 'Nessun suggerimento aggiuntivo disponibile per questa valuta.' : 'No additional suggestion available for this currency.') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="row-card">
                            <div>
                                <strong><?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?></strong>
                                <p><?= esc(ui_locale() === 'it' ? 'Compressione dei trasferimenti basata sui saldi netti.' : 'Transfer compression based on net balances.') ?></p>
                            </div>
                            <div class="list-table__meta">
                                <span class="amount-pill"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                                <?php if (has_permission('add_settlement', $activeHousehold, $currentUserId)): ?>
                                    <a class="button button--secondary" href="<?= route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) ?>">
                                        <?= esc(ui_locale() === 'it' ? 'Pareggia' : 'Settle') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>
<?php endforeach; ?>
<?= $this->endSection() ?>
