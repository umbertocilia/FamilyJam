<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$household = $reportContext['household'];
$identifier = (string) ($household['slug'] ?? $reportContext['membership']['household_slug'] ?? $household['id']);
$filters = $reportContext['filters'];
$categoryChartData = [];
foreach ($reportContext['byCategory'] as $currency => $rows) {
    foreach ($rows as $row) {
        $categoryChartData[$currency]['labels'][] = (string) $row['category_name'];
        $categoryChartData[$currency]['values'][] = (float) $row['amount'];
    }
}
$monthChartData = [];
foreach ($reportContext['byMonth'] as $currency => $rows) {
    foreach ($rows as $row) {
        $monthChartData[$currency]['labels'][] = report_period_label((string) $row['period']);
        $monthChartData[$currency]['values'][] = (float) $row['amount'];
    }
}
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('report.expenses.title')) ?></p>
        <h1><?= esc(ui_text('report.expenses.title')) ?></h1>
        <p class="hero__lead"><?= esc(ui_text('report.expenses.lead')) ?></p>
        <div class="quick-filter-bar" aria-label="Expense report navigation">
            <a class="module-chip" href="<?= route_url('reports.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
            <a class="module-chip" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_text('nav.expenses')) ?></a>
            <a class="module-chip" href="<?= route_url('reports.chores', $identifier) ?>"><?= esc(ui_text('nav.chores')) ?></a>
        </div>
    </div>

    <div class="hero__actions">
        <a class="button button--primary" href="<?= route_url('reports.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
        <a class="button button--secondary" href="<?= route_url('expenses.index', $identifier) ?>"><?= esc(ui_text('nav.expenses')) ?></a>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_text('common.filter')) ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Periodo e membro' : 'Period and member') ?></h2>
        </div>
    </div>

    <form class="auth-form auth-form--compact" method="get" action="<?= route_url('reports.expenses', $identifier) ?>">
        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Periodo' : 'Period') ?></span>
                <select name="months">
                    <?php foreach ([1, 3, 6, 12] as $months): ?>
                        <option value="<?= esc((string) $months) ?>" <?= (int) $filters['months'] === $months ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Ultimi ' . $months . ' mesi' : 'Last ' . $months . ' months') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Membro' : 'Member') ?></span>
                <select name="member_id">
                    <option value=""><?= esc(ui_text('common.all')) ?></option>
                    <?php foreach ($reportContext['members'] as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) ($filters['member_id'] ?? '') === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Aggiorna report' : 'Refresh report') ?></button>
            <a class="button button--secondary" href="<?= route_url('reports.expenses', $identifier) ?>"><?= esc(ui_text('common.reset')) ?></a>
        </div>
    </form>
</section>

<section class="summary-grid">
    <article class="metric-card">
        <span><?= esc(ui_locale() === 'it' ? 'Eventi spesa' : 'Expense events') ?></span>
        <strong><?= esc((string) $reportContext['summary']['expenses_count']) ?></strong>
        <small><?= esc(ui_locale() === 'it' ? 'Nel range selezionato' : 'In the selected range') ?></small>
    </article>
    <?php foreach ($reportContext['summary']['amount_by_currency'] as $row): ?>
        <article class="metric-card">
            <span><?= esc(ui_text('report.balance.overall')) ?> <?= esc((string) $row['currency']) ?></span>
            <strong><?= esc(money_format((string) $row['amount'], (string) $row['currency'])) ?></strong>
            <small><?= esc(ui_locale() === 'it' ? 'Totale registrato' : 'Logged total') ?></small>
        </article>
    <?php endforeach; ?>
    <?php foreach (($reportContext['overallBalance'] ?? []) as $row): ?>
        <article class="metric-card">
            <span><?= esc(ui_text('report.balance.overall')) ?> <?= esc((string) $row['currency']) ?></span>
            <strong><?= esc(money_format((string) $row['net_amount'], (string) $row['currency'])) ?></strong>
            <small><?= esc(balance_direction_label((string) $row['direction'])) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<?php foreach ($reportContext['byCategory'] as $currency => $rows): ?>
    <section class="content-grid">
        <article class="panel chart-card">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.expenses.chart.categories')) ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>
            <canvas data-expense-category-chart='<?= esc(json_encode($categoryChartData[$currency] ?? ['labels' => [], 'values' => []], JSON_THROW_ON_ERROR), 'attr') ?>'></canvas>
        </article>

        <article class="panel chart-card">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.expenses.chart.monthly')) ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>
            <canvas data-expense-month-chart='<?= esc(json_encode($monthChartData[$currency] ?? ['labels' => [], 'values' => []], JSON_THROW_ON_ERROR), 'attr') ?>'></canvas>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.expenses.by_category')) ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>
            <div class="list-table">
                <?php foreach ($rows as $row): ?>
                    <div class="list-table__row">
                        <div>
                            <strong><?= esc((string) $row['category_name']) ?></strong>
                            <p><?= esc((string) $row['expenses_count']) ?> <?= esc(ui_locale() === 'it' ? 'spese' : 'expenses') ?> / <?= esc((string) $row['share_percent']) ?>%</p>
                        </div>
                        <div class="list-table__meta">
                            <strong><?= esc(money_format((string) $row['amount'], $currency)) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.expenses.by_user')) ?></p>
            <h2><?= esc(ui_locale() === 'it' ? 'Pagato vs dovuto' : 'Paid vs owed') ?></h2>
                </div>
            </div>
            <div class="list-table">
                <?php foreach (($reportContext['byUser'][$currency] ?? []) as $row): ?>
                    <div class="list-table__row">
                        <div>
                            <strong><?= esc((string) $row['display_name']) ?></strong>
                            <p><?= esc(ui_locale() === 'it' ? 'Pagato' : 'Paid') ?> <?= esc(money_format((string) $row['paid_amount'], $currency)) ?> / <?= esc(ui_locale() === 'it' ? 'Dovuto' : 'Owed') ?> <?= esc(money_format((string) $row['owed_amount'], $currency)) ?></p>
                        </div>
                        <div class="list-table__meta">
                            <span class="<?= esc(balance_direction_badge_class((string) ($row['net_amount_cents'] > 0 ? 'gets_back' : ($row['net_amount_cents'] < 0 ? 'owes' : 'settled')))) ?>"><?= esc(balance_direction_label((string) ($row['net_amount_cents'] > 0 ? 'gets_back' : ($row['net_amount_cents'] < 0 ? 'owes' : 'settled')))) ?></span>
                            <strong><?= esc(money_format((string) $row['net_amount'], $currency)) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <?php if (($reportContext['balanceByGroup'][$currency] ?? []) !== []): ?>
        <section class="panel">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow"><?= esc(ui_text('report.balance.by_group')) ?></p>
                    <h2><?= esc($currency) ?></h2>
                </div>
            </div>
            <div class="list-table">
                <?php foreach ($reportContext['balanceByGroup'][$currency] as $row): ?>
                    <div class="list-table__row">
                        <div>
                            <strong><?= esc((string) $row['group_name']) ?></strong>
                        </div>
                        <div class="list-table__meta">
                            <span class="<?= esc(balance_direction_badge_class((string) $row['direction'])) ?>"><?= esc(balance_direction_label((string) $row['direction'])) ?></span>
                            <strong><?= esc(money_format((string) $row['net_amount'], $currency)) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow"><?= esc(ui_text('report.expenses.recent')) ?></p>
            <h2><?= esc(ui_text('report.expenses.recent')) ?></h2>
        </div>
    </div>

    <div class="list-table">
        <?php if ($reportContext['recentExpenses'] === []): ?>
            <?php $title = ui_locale() === 'it' ? 'Nessuna spesa nel range' : 'No expenses in range'; $message = ui_locale() === 'it' ? 'Allarga il periodo o registra nuove spese.' : 'Widen the period or log new expenses.'; $actionLabel = ui_locale() === 'it' ? 'Nuova spesa' : 'New expense'; $actionHref = route_url('expenses.create', $identifier); ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <?php foreach ($reportContext['recentExpenses'] as $expense): ?>
                <a class="list-table__row" href="<?= route_url('expenses.show', $identifier, $expense['id']) ?>">
                    <div>
                        <strong><?= esc((string) $expense['title']) ?></strong>
                        <p><?= esc((string) ($expense['category_name'] ?? ui_text('category.uncategorized'))) ?> / <?= esc((string) $expense['expense_date']) ?></p>
                    </div>
                    <div class="list-table__meta">
                        <span class="badge badge--expense-split"><?= esc(expense_split_label((string) $expense['split_method'])) ?></span>
                        <strong><?= esc(money_format((string) $expense['total_amount'], (string) $expense['currency'])) ?></strong>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
