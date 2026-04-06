<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
/** @var array<string, mixed> $expenseDetailContext */
$membership = $expenseDetailContext['membership'];
$expense = $expenseDetailContext['expense'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Expense Detail</p>
        <h1><?= esc((string) $expense['title']) ?></h1>
        <p class="hero__lead"><?= esc((string) ($expense['description'] ?? 'Shared expense recorded in the household ledger.')) ?></p>
        <div class="quick-filter-bar" aria-label="Expense path">
            <a class="module-chip" href="<?= route_url('expenses.index', $identifier) ?>">Expense log</a>
            <a class="module-chip" href="<?= route_url('balances.overview', $identifier) ?>">Balance impact</a>
            <a class="module-chip" href="<?= route_url('reports.expenses', $identifier) ?>">Report</a>
        </div>
    </div>

    <div class="hero__actions">
        <span class="badge <?= esc(expense_status_badge_class((string) $expense['status'])) ?>"><?= esc(expense_status_label((string) $expense['status'])) ?></span>
        <span class="badge badge--expense-split"><?= esc(expense_split_label((string) $expense['split_method'])) ?></span>
        <?php if ($canEditExpense): ?>
            <a class="button button--primary" href="<?= route_url('expenses.edit', $identifier, $expense['id']) ?>">Edit</a>
        <?php endif; ?>
        <?php if ($canDeleteExpense): ?>
            <form method="post" action="<?= route_url('expenses.delete', $identifier, $expense['id']) ?>" onsubmit="return confirm('Delete this expense?');">
                <?= csrf_field() ?>
                <button class="button button--secondary" type="submit">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="summary-grid">
    <?php $label = 'Total'; $value = money_format((string) $expense['total_amount'], (string) $expense['currency']); $hint = (string) $expense['expense_date']; $tone = 'default'; ?>
    <?= $this->include('partials/components/stat_card') ?>
    <?php $label = 'Category'; $value = (string) ($expense['category_name'] ?? 'Uncategorized'); $hint = (string) ($expense['category_icon'] ?? 'Expense classification'); $tone = 'default'; ?>
    <?= $this->include('partials/components/stat_card') ?>
    <?php $label = 'Created by'; $value = (string) ($expense['created_by_name'] ?? 'N/A'); $hint = 'Last update: ' . (string) ($expense['updated_by_name'] ?? $expense['created_by_name'] ?? 'N/A'); $tone = 'default'; ?>
    <?= $this->include('partials/components/stat_card') ?>
    <?php $label = 'Receipt'; $value = ! empty($expense['receipt_attachment_id']) ? 'Available' : 'Missing'; $hint = (string) ($expense['receipt_original_name'] ?? 'No attachment'); $tone = 'default'; ?>
    <?= $this->include('partials/components/stat_card') ?>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Overview</p>
                <h2>Expense details</h2>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-card">
                <span>Currency</span>
                <strong><?= esc((string) $expense['currency']) ?></strong>
            </div>
            <div class="detail-card">
                <span>Split method</span>
                <strong><?= esc(expense_split_label((string) $expense['split_method'])) ?></strong>
            </div>
            <div class="detail-card">
                <span>Status</span>
                <strong><?= esc(expense_status_label((string) $expense['status'])) ?></strong>
            </div>
            <div class="detail-card">
                <span>Description</span>
                <strong><?= esc((string) ($expense['description'] ?? 'No notes')) ?></strong>
            </div>
            <div class="detail-card">
                <span>Receipt</span>
                <strong>
                    <?php if (! empty($expense['receipt_attachment_id'])): ?>
                        <a href="<?= route_url('expenses.receipt', $identifier, $expense['id']) ?>"><?= esc((string) ($expense['receipt_original_name'] ?? 'Download file')) ?></a>
                    <?php else: ?>
                        No receipt
                    <?php endif; ?>
                </strong>
            </div>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Payers</p>
                <h2>Who paid</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php if ($expenseDetailContext['payers'] === []): ?>
                <?php $title = 'No payers recorded'; $message = 'This expense does not yet expose readable payer rows.'; $actionLabel = null; $actionHref = null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($expenseDetailContext['payers'] as $payer): ?>
                    <div class="row-card">
                        <div>
                            <strong><?= esc((string) ($payer['display_name'] ?? $payer['email'])) ?></strong>
                            <p><?= esc((string) $payer['email']) ?></p>
                        </div>
                        <span class="amount-pill"><?= esc(money_format((string) $payer['amount_paid'], (string) $expense['currency'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Participants</p>
                <h2>Participant shares</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php if ($expenseDetailContext['splits'] === []): ?>
                <?php $title = 'No participants detected'; $message = 'Participant shares will appear here once calculated by the split service.'; $actionLabel = null; $actionHref = null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($expenseDetailContext['splits'] as $split): ?>
                    <div class="row-card">
                        <div>
                            <strong><?= esc((string) ($split['display_name'] ?? $split['email'])) ?></strong>
                            <p>
                                <?php if ($expense['split_method'] === 'percentage'): ?>
                                    <?= esc((string) $split['percentage']) ?>%
                                <?php elseif ($expense['split_method'] === 'shares'): ?>
                                    <?= esc((string) $split['share_units']) ?> shares
                                <?php else: ?>
                                    Calculated by the split service
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="amount-pill"><?= esc(money_format((string) $split['owed_amount'], (string) $expense['currency'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Audit</p>
                <h2>Change history</h2>
            </div>
        </div>

        <?php if ($expenseDetailContext['audit_logs'] === []): ?>
            <?php
            $title = 'No audit events yet';
            $message = 'Future mutations will be tracked through expense.created, expense.updated and expense.deleted.';
            $actionLabel = null;
            $actionHref = null;
            ?>
            <?= $this->include('partials/components/empty_state') ?>
        <?php else: ?>
            <?php
            $events = array_map(
                static fn (array $audit): array => [
                    'title' => (string) ($audit['action'] ?? 'expense.event'),
                    'body' => (string) ($audit['actor_name'] ?? 'Operation recorded in the audit log'),
                    'meta' => (string) ($audit['created_at'] ?? ''),
                ],
                $expenseDetailContext['audit_logs'],
            );
            ?>
            <?= $this->include('partials/components/timeline') ?>
        <?php endif; ?>
    </article>
</section>
<?= $this->endSection() ?>
