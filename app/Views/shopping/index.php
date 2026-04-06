<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $shoppingIndexContext['membership'];
$lists = $shoppingIndexContext['lists'];
$summary = $shoppingIndexContext['summary'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManageShopping = ! empty($shoppingIndexContext['canManageShopping']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('nav.shopping')) ?></p>
        <h1><?= esc((string) $membership['household_name']) ?> <?= esc(ui_locale() === 'it' ? 'shopping list' : 'shopping lists') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Liste shopping mobile-first con quick add, stato acquisto e conversione pulita in expense.' : 'Mobile-first shopping boards with quick add, purchase status and clean expense conversion.') ?></p>
        <div class="quick-filter-bar" aria-label="Shopping shortcuts">
            <a class="module-chip" href="<?= route_url('shopping.index', $identifier) ?>">Lists</a>
            <?php if ($canManageShopping): ?>
                <a class="module-chip" href="<?= route_url('shopping.create', $identifier) ?>">New list</a>
            <?php endif; ?>
            <a class="module-chip" href="<?= route_url('expenses.index', $identifier) ?>">Expense conversion</a>
        </div>
    </div>
    <div class="hero__actions">
        <?php if ($canManageShopping): ?>
            <a class="button button--primary" href="<?= route_url('shopping.create', $identifier) ?>">New list</a>
        <?php endif; ?>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Liste' : 'Lists') ?></span><strong><?= esc((string) $summary['lists']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Item aperti' : 'Open items') ?></span><strong><?= esc((string) $summary['open_items']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Acquistati' : 'Purchased') ?></span><strong><?= esc((string) $summary['purchased_items']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Urgenti' : 'Urgent') ?></span><strong><?= esc((string) $summary['urgent_items']) ?></strong></article>
</section>

<section class="content-grid">
    <?php if ($lists === []): ?>
        <article class="panel">
            <?php $title = ui_locale() === 'it' ? 'Nessuna shopping list' : 'No shopping lists yet'; $message = ui_locale() === 'it' ? 'Crea la prima lista per usare quick add, acquisto multiplo e conversione in expense.' : 'Create the first list to start using quick add, batch purchase and expense conversion.'; $actionLabel = $canManageShopping ? (ui_locale() === 'it' ? 'Crea lista' : 'Create list') : null; $actionHref = $canManageShopping ? route_url('shopping.create', $identifier) : null; $icon = 'fas fa-shopping-cart'; ?>
            <?= $this->include('partials/components/empty_state') ?>
        </article>
    <?php else: ?>
        <?php foreach ($lists as $list): ?>
            <article class="panel shopping-list-card">
                <div class="expense-row__header">
                    <div class="stack stack--compact">
                        <strong><?= esc((string) $list['name']) ?></strong>
                        <p><?= esc((string) ($list['created_by_name'] ?? 'FamilyJam')) ?></p>
                    </div>
                    <?php if ((int) ($list['is_default'] ?? 0) === 1): ?>
                        <span class="badge badge--expense-active">Default</span>
                    <?php endif; ?>
                </div>

                <div class="expense-row__meta">
                    <span class="badge badge--shopping-open"><?= esc((string) ($list['open_count'] ?? 0)) ?> open</span>
                    <span class="badge badge--expense-step"><?= esc((string) ($list['purchased_count'] ?? 0)) ?> purchased</span>
                    <?php if ((int) ($list['urgent_open_count'] ?? 0) > 0): ?>
                        <span class="badge badge--shopping-urgent"><?= esc((string) $list['urgent_open_count']) ?> urgent</span>
                    <?php endif; ?>
                </div>

                <div class="hero__actions">
                    <a class="button button--primary" href="<?= route_url('shopping.show', $identifier, $list['id']) ?>">Open list</a>
                    <?php if ($canManageShopping): ?>
                        <a class="button button--secondary" href="<?= route_url('shopping.edit', $identifier, $list['id']) ?>">Edit</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
