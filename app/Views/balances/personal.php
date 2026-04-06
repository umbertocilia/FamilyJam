<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $balanceContext['membership'];
$household = $balanceContext['household'];
$personalMember = $balanceContext['personalMember'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Saldo personale' : 'Personal balance') ?></p>
        <h1><?= esc((string) ($personalMember['display_name'] ?? $personalMember['email'] ?? (ui_locale() === 'it' ? 'Membro corrente' : 'Current member'))) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Vista personale del saldo netto, delle relazioni pairwise reali e dei suggerimenti semplificati collegati al profilo corrente.' : 'Personal view of net balance, real pairwise relationships and simplified suggestions linked to the current profile.') ?></p>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('balances.overview', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica household' : 'Household overview') ?></a>
        <a class="button button--secondary" href="<?= route_url('balances.pairwise', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?></a>
    </div>
</section>

<section class="summary-grid">
    <?php foreach ($balanceContext['personalBalances'] as $currency => $row): ?>
        <article class="metric-card">
            <span><?= esc($currency) ?></span>
            <strong><?= esc(money_format((string) $row['net_amount'], $currency)) ?></strong>
            <small><?= esc(balance_direction_label((string) $row['direction'])) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<?php if (($balanceContext['personalGroupBalances'] ?? []) !== []): ?>
    <?php foreach ($balanceContext['personalGroupBalances'] as $currency => $rows): ?>
        <section class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.balance.by_member_group')) ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>
            <div class="stack-list">
                <?php foreach ($rows as $row): ?>
                    <div class="row-card">
                        <div>
                            <strong><?= esc((string) $row['group_name']) ?></strong>
                            <p><?= esc(balance_direction_label((string) $row['direction'])) ?></p>
                        </div>
                        <span class="amount-pill"><?= esc(money_format((string) $row['net_amount'], $currency)) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($balanceContext['personalBalances'] === []): ?>
    <section class="panel">
        <div class="row-card row-card--vertical">
            <strong><?= esc(ui_locale() === 'it' ? 'Nessun saldo personale disponibile' : 'No personal balance available') ?></strong>
            <p><?= esc(ui_locale() === 'it' ? 'Questo membro non e ancora coinvolto in spese o settlement registrati.' : 'This member is not involved in any recorded expenses or settlements yet.') ?></p>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($balanceContext['personalPairwiseBalances'] as $currency => $rows): ?>
    <section class="content-grid">
        <article class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Pairwise reale' : 'Real pairwise') ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>

            <div class="stack-list">
                <?php if ($rows === []): ?>
                    <div class="row-card row-card--vertical">
                        <strong><?= esc(ui_locale() === 'it' ? 'Nessuna esposizione aperta' : 'No open exposure') ?></strong>
                        <p><?= esc(ui_locale() === 'it' ? 'Non risultano debiti o crediti verso altri membri per questa valuta.' : 'There are no debts or credits toward other members for this currency.') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="row-card">
                            <div>
                                <strong><?= esc((string) $row['from_user_name']) ?> <?= esc(ui_locale() === 'it' ? 'deve a' : 'owes') ?> <?= esc((string) $row['to_user_name']) ?></strong>
                                <p><?= esc(ui_locale() === 'it' ? 'Relazione reale nel ledger corrente.' : 'Real relationship in the current ledger.') ?></p>
                            </div>
                            <span class="amount-pill"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel panel--accent">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_locale() === 'it' ? 'Semplificato' : 'Simplified') ?></p>
                    <h2><?= esc(ui_locale() === 'it' ? 'Trasferimenti suggeriti' : 'Suggested transfers') ?></h2>
                </div>
            </div>

            <div class="stack-list">
                <?php $rows = $balanceContext['personalSimplifiedTransfers'][$currency] ?? []; ?>
                <?php if ($rows === []): ?>
                    <div class="row-card row-card--vertical">
                        <strong><?= esc(ui_locale() === 'it' ? 'Nessun suggerimento' : 'No suggestion') ?></strong>
                        <p><?= esc(ui_locale() === 'it' ? 'I suggerimenti semplificati non coinvolgono questo membro o la household non li usa.' : 'Simplified suggestions do not involve this member or this household does not use them.') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="row-card">
                            <div>
                                <strong><?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?></strong>
                                <p><?= esc(ui_locale() === 'it' ? 'Suggerimento semplificato basato sui saldi netti.' : 'Simplified suggestion based on net balances.') ?></p>
                            </div>
                            <span class="amount-pill"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>
<?php endforeach; ?>
<?= $this->endSection() ?>
