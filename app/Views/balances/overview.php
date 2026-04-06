<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $balanceContext['membership'];
$household = $balanceContext['household'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h1><?= esc(ui_text('nav.balances')) ?></h1>
    </div>
    <div class="col-sm-6">
        <div class="float-sm-right">
            <a class="btn btn-default" href="<?= route_url('balances.personal', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Il mio saldo' : 'My balance') ?></a>
            <a class="btn btn-default ml-2" href="<?= route_url('balances.pairwise', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?></a>
            <?php if (has_permission('add_settlement', $activeHousehold, $currentUserId)): ?>
                <a class="btn btn-primary ml-2" href="<?= route_url('settlements.create', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Nuovo rimborso' : 'New settlement') ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Valuta base' : 'Base currency') ?></span>
                <span class="info-box-number"><?= esc((string) $household['base_currency']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Eventi spesa' : 'Expense events') ?></span>
                <span class="info-box-number"><?= esc((string) count($balanceContext['expensesForLedger'])) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-exchange-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Eventi rimborso' : 'Settlement events') ?></span>
                <span class="info-box-number"><?= esc((string) count($balanceContext['settlementsForLedger'])) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-layer-group"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Valute attive' : 'Currency buckets') ?></span>
                <span class="info-box-number"><?= esc((string) count($balanceContext['netBalances'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($balanceContext['netBalances'] === []): ?>
    <div class="card card-outline card-primary">
        <div class="card-body">
            <?php
            $title = ui_locale() === 'it' ? 'Nessuna attivita contabile' : 'No accounting activity yet';
            $message = ui_locale() === 'it' ? 'Aggiungi una spesa o un settlement per iniziare a vedere saldi, relazioni pairwise e suggerimenti di semplificazione.' : 'Add an expense or a settlement to start seeing balances, pairwise transfers and simplification suggestions.';
            $actionLabel = ui_locale() === 'it' ? 'Nuovo settlement' : 'New settlement';
            $actionHref = has_permission('add_settlement', $activeHousehold, $currentUserId) ? route_url('settlements.create', $identifier) : route_url('expenses.index', $identifier);
            $icon = 'fas fa-wallet';
            ?>
            <?= $this->include('partials/components/empty_state') ?>
        </div>
    </div>
<?php endif; ?>

<?php if (($balanceContext['groupBalances'] ?? []) !== []): ?>
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i><?= esc(ui_text('report.balance.by_group')) ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($balanceContext['groupBalances'] as $group): ?>
                    <?php
                    $groupCurrencyBuckets = is_array($group['currencies'] ?? null)
                        ? $group['currencies']
                        : [];
                    $groupNetBalances = is_array($group['netBalances'] ?? null)
                        ? $group['netBalances']
                        : [];
                    $personalRows = [];
                    foreach ($groupNetBalances as $currencyCode => $groupRows) {
                        foreach ($groupRows as $groupRow) {
                            if ((int) $groupRow['user_id'] === (int) ($currentUserId ?? 0)) {
                                $personalRows[] = [
                                    'currency' => $currencyCode,
                                    'net_amount' => $groupRow['net_amount'],
                                    'direction' => $groupRow['direction'],
                                ];
                            }
                        }
                    }
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon" style="background: <?= esc((string) ($group['group_color'] ?? '#17a2b8')) ?>; color: #fff;">
                                <i class="fas fa-wallet"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc((string) $group['group_name']) ?></span>
                                <span class="text-muted text-sm"><?= esc((string) count($groupCurrencyBuckets !== [] ? $groupCurrencyBuckets : $groupNetBalances)) ?> <?= esc(ui_locale() === 'it' ? 'valute' : 'currencies') ?></span>
                                <?php foreach ($personalRows as $personalRow): ?>
                                    <span class="d-block text-sm">
                                        <?= esc(balance_direction_label((string) $personalRow['direction'])) ?>
                                        <?= esc(money_format((string) $personalRow['net_amount'], (string) $personalRow['currency'])) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($balanceContext['netBalances'] as $currency => $rows): ?>
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-2"></i><?= esc(ui_locale() === 'it' ? 'Saldi netti' : 'Net balances') ?> <?= esc((string) $currency) ?></h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th><?= esc(ui_locale() === 'it' ? 'Membro' : 'Member') ?></th>
                            <th><?= esc(ui_locale() === 'it' ? 'Direzione' : 'Direction') ?></th>
                            <th class="text-right"><?= esc(ui_locale() === 'it' ? 'Importo' : 'Amount') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= esc((string) $row['display_name']) ?></strong>
                                    <div class="text-muted text-sm"><?= esc((string) ($row['email'] ?? '')) ?></div>
                                </td>
                                <td><span class="<?= esc(balance_direction_badge_class((string) $row['direction'])) ?>"><?= esc(balance_direction_label((string) $row['direction'])) ?></span></td>
                                <td class="text-right"><?= esc(money_format((string) $row['net_amount'], $currency)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-random mr-2"></i><?= esc(ui_locale() === 'it' ? 'Chi deve a chi' : 'Who owes whom') ?> (<?= esc((string) $currency) ?>)</h3>
                </div>
                <div class="card-body p-0">
                    <?php $pairwiseRows = $balanceContext['pairwiseBalances'][$currency] ?? []; ?>
                    <?php if ($pairwiseRows === []): ?>
                        <div class="p-4">
                            <?php $title = ui_locale() === 'it' ? 'Nessun saldo aperto' : 'No open balances'; $message = ui_locale() === 'it' ? 'Tutti risultano in pari per questa valuta.' : 'Everyone is currently settled for this currency.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-check-circle'; ?>
                            <?= $this->include('partials/components/empty_state') ?>
                        </div>
                    <?php else: ?>
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php foreach ($pairwiseRows as $row): ?>
                                <li class="item">
                                    <div class="product-info ml-0">
                                        <a href="<?= has_permission('add_settlement', $activeHousehold, $currentUserId) ? route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) : route_url('balances.pairwise', $identifier) ?>" class="product-title">
                                            <?= esc((string) $row['from_user_name']) ?> <?= esc(ui_locale() === 'it' ? 'deve a' : 'owes') ?> <?= esc((string) $row['to_user_name']) ?>
                                            <span class="badge badge-success float-right"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                                        </a>
                                        <span class="product-description">
                                            <?= esc(ui_locale() === 'it' ? 'Saldo reale derivato dagli eventi registrati.' : 'Real balance derived from recorded events.') ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (! empty($household['simplify_debts'])): ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-magic mr-2"></i><?= esc(ui_locale() === 'it' ? 'Trasferimenti suggeriti' : 'Suggested transfers') ?> (<?= esc((string) $currency) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <?php $suggestions = $balanceContext['simplifiedTransfers'][$currency] ?? []; ?>
                <?php if ($suggestions === []): ?>
                    <div class="p-4">
                        <?php $title = ui_locale() === 'it' ? 'Nessun trasferimento suggerito' : 'No suggested transfers'; $message = ui_locale() === 'it' ? 'I saldi netti sono gia in pari per questa valuta.' : 'Net balances are already settled for this currency.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-magic'; ?>
                        <?= $this->include('partials/components/empty_state') ?>
                    </div>
                <?php else: ?>
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        <?php foreach ($suggestions as $row): ?>
                            <li class="item">
                                <div class="product-info ml-0">
                                    <a href="<?= has_permission('add_settlement', $activeHousehold, $currentUserId) ? route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) : route_url('balances.pairwise', $identifier) ?>" class="product-title">
                                        <?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?>
                                        <span class="badge badge-info float-right"><?= esc(money_format((string) $row['amount'], $currency)) ?></span>
                                    </a>
                                    <span class="product-description">
                                        <?= esc(ui_locale() === 'it' ? 'Suggerimento semplificato. Non modifica il ledger reale.' : 'Simplified suggestion. It does not modify the real ledger.') ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (($balanceContext['groupBalances'] ?? []) !== []): ?>
    <div class="card card-outline card-secondary mt-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i><?= esc(ui_text('report.balance.by_group')) ?></h3>
        </div>
        <div class="card-body">
            <?php foreach ($balanceContext['groupBalances'] as $group): ?>
                <div class="card card-outline mb-4" id="expense-group-<?= esc((string) ($group['group_id'] ?? 'general')) ?>">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-folder-open mr-2"<?= ! empty($group['group_color']) ? ' style="color:' . esc((string) $group['group_color']) . '"' : '' ?>></i>
                            <?= esc((string) $group['group_name']) ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($group['netBalances'] as $currency => $rows): ?>
                            <div class="row">
                                <div class="col-lg-5">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title"><?= esc(ui_locale() === 'it' ? 'Saldi del gruppo' : 'Group balances') ?> <?= esc((string) $currency) ?></h3>
                                        </div>
                                        <div class="card-body table-responsive p-0">
                                            <table class="table table-hover">
                                                <thead>
                                                <tr>
                                                    <th><?= esc(ui_locale() === 'it' ? 'Membro' : 'Member') ?></th>
                                                    <th><?= esc(ui_locale() === 'it' ? 'Direzione' : 'Direction') ?></th>
                                                    <th class="text-right"><?= esc(ui_locale() === 'it' ? 'Importo' : 'Amount') ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <tr>
                                                        <td><?= esc((string) $row['display_name']) ?></td>
                                                        <td><span class="<?= esc(balance_direction_badge_class((string) $row['direction'])) ?>"><?= esc(balance_direction_label((string) $row['direction'])) ?></span></td>
                                                        <td class="text-right"><?= esc(money_format((string) $row['net_amount'], (string) $currency)) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                            <h3 class="card-title"><?= esc(ui_locale() === 'it' ? 'Chi deve a chi nel gruppo' : 'Who owes whom in this group') ?> (<?= esc((string) $currency) ?>)</h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php $groupPairwise = $group['pairwiseBalances'][$currency] ?? []; ?>
                                            <?php if ($groupPairwise === []): ?>
                                                <div class="p-3 text-muted"><?= esc(ui_locale() === 'it' ? 'Nessun saldo aperto nel gruppo per questa valuta.' : 'No open group balances for this currency.') ?></div>
                                            <?php else: ?>
                                                <ul class="products-list product-list-in-card pl-2 pr-2">
                                                    <?php foreach ($groupPairwise as $row): ?>
                                                        <li class="item">
                                                            <div class="product-info ml-0">
                                                                <a href="<?= has_permission('add_settlement', $activeHousehold, $currentUserId) ? route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&expense_group_id=' . rawurlencode((string) ($group['group_id'] ?? '')) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) : route_url('balances.pairwise', $identifier) ?>" class="product-title">
                                                                    <?= esc((string) $row['from_user_name']) ?> <?= esc(ui_locale() === 'it' ? 'deve a' : 'owes') ?> <?= esc((string) $row['to_user_name']) ?>
                                                                    <span class="badge badge-success float-right"><?= esc(money_format((string) $row['amount'], (string) $currency)) ?></span>
                                                                </a>
                                                                <span class="product-description">
                                                                    <?= esc(ui_locale() === 'it' ? 'Pareggio riferito solo a questo gruppo spesa.' : 'Settlement scope limited to this expense group.') ?>
                                                                </span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (! empty($household['simplify_debts'])): ?>
                                        <div class="card card-outline card-info">
                                            <div class="card-header">
                                                <h3 class="card-title"><?= esc(ui_locale() === 'it' ? 'Suggerimenti semplificati del gruppo' : 'Simplified group suggestions') ?> (<?= esc((string) $currency) ?>)</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <?php $groupSimplified = $group['simplifiedTransfers'][$currency] ?? []; ?>
                                                <?php if ($groupSimplified === []): ?>
                                                    <div class="p-3 text-muted"><?= esc(ui_locale() === 'it' ? 'Nessun trasferimento suggerito nel gruppo.' : 'No suggested transfers inside this group.') ?></div>
                                                <?php else: ?>
                                                    <ul class="products-list product-list-in-card pl-2 pr-2">
                                                        <?php foreach ($groupSimplified as $row): ?>
                                                            <li class="item">
                                                                <div class="product-info ml-0">
                                                                    <a href="<?= has_permission('add_settlement', $activeHousehold, $currentUserId) ? route_url('settlements.create', $identifier) . '?from_user_id=' . rawurlencode((string) $row['from_user_id']) . '&to_user_id=' . rawurlencode((string) $row['to_user_id']) . '&expense_group_id=' . rawurlencode((string) ($group['group_id'] ?? '')) . '&currency=' . rawurlencode((string) $currency) . '&amount=' . rawurlencode((string) $row['amount']) : route_url('balances.pairwise', $identifier) ?>" class="product-title">
                                                                        <?= esc((string) $row['from_user_name']) ?> -> <?= esc((string) $row['to_user_name']) ?>
                                                                        <span class="badge badge-info float-right"><?= esc(money_format((string) $row['amount'], (string) $currency)) ?></span>
                                                                    </a>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
