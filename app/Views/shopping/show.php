<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $shoppingDetailContext['membership'];
$list = $shoppingDetailContext['list'];
$openItems = $shoppingDetailContext['openItems'];
$purchasedItems = $shoppingDetailContext['purchasedItems'];
$members = $shoppingDetailContext['members'];
$categories = $shoppingDetailContext['categories'];
$defaults = $shoppingDetailContext['conversionDefaults'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManageShopping = ! empty($shoppingDetailContext['canManageShopping']);
$canCreateExpense = ! empty($shoppingDetailContext['canCreateExpense']);
$openBulkFormId = 'shopping-open-bulk-form';
$convertFormId = 'shopping-convert-form';
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Shopping List</p>
        <h1><?= esc((string) $list['name']) ?></h1>
        <p class="hero__lead">Quick add in alto, lista aperta sotto e conversione a expense solo quando serve.</p>
        <div class="quick-filter-bar" aria-label="Scorciatoie lista">
            <span class="badge badge--shopping-open"><?= esc((string) ($list['open_count'] ?? 0)) ?> open</span>
            <span class="badge badge--expense-step"><?= esc((string) ($list['purchased_count'] ?? 0)) ?> purchased</span>
            <?php if ((int) ($list['urgent_open_count'] ?? 0) > 0): ?>
                <span class="badge badge--shopping-urgent"><?= esc((string) $list['urgent_open_count']) ?> urgent</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('shopping.index', $identifier) ?>">All lists</a>
        <?php if ($canManageShopping): ?>
            <a class="button button--secondary" href="<?= route_url('shopping.edit', $identifier, $list['id']) ?>">Modifica lista</a>
            <form method="post" action="<?= route_url('shopping.delete', $identifier, $list['id']) ?>" onsubmit="return confirm('Eliminare questa lista?');">
                <?= csrf_field() ?>
                <button class="button button--secondary" type="submit">Elimina</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span>Open items</span><strong><?= esc((string) ($list['open_count'] ?? 0)) ?></strong></article>
    <article class="metric-card"><span>Purchased</span><strong><?= esc((string) ($list['purchased_count'] ?? 0)) ?></strong></article>
    <article class="metric-card"><span>Urgent</span><strong><?= esc((string) ($list['urgent_open_count'] ?? 0)) ?></strong></article>
    <article class="metric-card"><span>Totale items</span><strong><?= esc((string) ($list['items_count'] ?? 0)) ?></strong></article>
</section>

<?php if ($canManageShopping): ?>
    <section class="panel shopping-quick-add-panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Quick Add</p>
                <h2>Aggiunta rapida</h2>
            </div>
        </div>
        <form class="shopping-quick-add" method="post" action="<?= route_url('shopping.items.store', $identifier, $list['id']) ?>">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="Aggiungi item..." value="<?= esc(old('name', '')) ?>" maxlength="160" required data-shopping-quick-add-input>
            <input type="number" name="quantity" value="<?= esc(old('quantity', '1')) ?>" step="0.01" min="0.01">
            <input type="text" name="unit" value="<?= esc(old('unit', '')) ?>" placeholder="unit">
            <select name="priority">
                <?php foreach (['urgent', 'high', 'normal', 'low'] as $priority): ?>
                    <option value="<?= esc($priority) ?>" <?= old('priority', 'normal') === $priority ? 'selected' : '' ?>><?= esc(shopping_priority_label($priority)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button--primary" type="submit">Aggiungi</button>
        </form>
    </section>
<?php endif; ?>

<section class="content-grid content-grid--wide">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Open</p>
                <h2>Da acquistare</h2>
            </div>
            <?php if ($canManageShopping && $openItems !== []): ?>
                <form id="<?= esc($openBulkFormId) ?>" method="post" action="<?= route_url('shopping.items.bulk', $identifier, $list['id']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mark_as" value="purchased">
                </form>
                <button class="button button--primary" type="submit" form="<?= esc($openBulkFormId) ?>">
                    Segna selezionati come purchased
                </button>
            <?php endif; ?>
        </div>

        <div class="stack">
            <?php if ($openItems === []): ?>
                <?php $title = 'Nessun item aperto'; $message = 'La lista e pulita. Usa quick add per aggiungere il prossimo acquisto.'; $actionLabel = null; $actionHref = null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($openItems as $item): ?>
                    <article class="row-card shopping-item-card">
                        <div class="shopping-item-card__header">
                            <?php if ($canManageShopping): ?>
                                <label class="checkbox-row">
                                    <input type="checkbox" name="item_ids[]" value="<?= esc((string) $item['id']) ?>" form="<?= esc($openBulkFormId) ?>">
                                    <span></span>
                                </label>
                            <?php endif; ?>
                            <div class="stack stack--compact">
                                <strong><?= esc((string) $item['name']) ?></strong>
                                <p><?= esc(shopping_quantity_label((string) $item['quantity'], $item['unit'] ?? null)) ?><?php if (! empty($item['category'])): ?> - <?= esc((string) $item['category']) ?><?php endif; ?></p>
                            </div>
                            <span class="badge <?= esc(shopping_priority_badge_class((string) $item['priority'])) ?>"><?= esc(shopping_priority_label((string) $item['priority'])) ?></span>
                        </div>

                        <?php if (! empty($item['notes'])): ?>
                            <p><?= esc((string) $item['notes']) ?></p>
                        <?php endif; ?>

                        <div class="expense-row__meta">
                            <?php if (! empty($item['assigned_user_name'])): ?>
                                <span class="badge badge--expense-step"><?= esc((string) $item['assigned_user_name']) ?></span>
                            <?php endif; ?>
                            <span class="badge badge--shopping-open">Pos <?= esc((string) $item['position']) ?></span>
                        </div>

                        <?php if ($canManageShopping): ?>
                            <div class="hero__actions">
                                <form method="post" action="<?= route_url('shopping.items.toggle', $identifier, $item['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="shopping_list_id" value="<?= esc((string) $list['id']) ?>">
                                    <button class="button button--primary" type="submit">Purchased</button>
                                </form>
                            </div>

                            <details class="shopping-item-details">
                                <summary>Modifica item</summary>
                                <form class="auth-form auth-form--compact" method="post" action="<?= route_url('shopping.items.update', $identifier, $item['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="shopping_list_id" value="<?= esc((string) $list['id']) ?>">
                                    <div class="form-grid">
                                        <label class="field field--full">
                                            <span>Nome</span>
                                            <input type="text" name="name" value="<?= esc((string) $item['name']) ?>" required>
                                        </label>
                                        <label class="field">
                                            <span>Quantita</span>
                                            <input type="number" name="quantity" value="<?= esc((string) $item['quantity']) ?>" step="0.01" min="0.01" required>
                                        </label>
                                        <label class="field">
                                            <span>Unit</span>
                                            <input type="text" name="unit" value="<?= esc((string) ($item['unit'] ?? '')) ?>">
                                        </label>
                                        <label class="field">
                                            <span>Categoria</span>
                                            <input type="text" name="category" value="<?= esc((string) ($item['category'] ?? '')) ?>">
                                        </label>
                                        <label class="field">
                                            <span>Priorita</span>
                                            <select name="priority">
                                                <?php foreach (['urgent', 'high', 'normal', 'low'] as $priority): ?>
                                                    <option value="<?= esc($priority) ?>" <?= (string) $item['priority'] === $priority ? 'selected' : '' ?>><?= esc(shopping_priority_label($priority)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="field">
                                            <span>Assegnato a</span>
                                            <select name="assigned_user_id">
                                                <option value="">None</option>
                                                <?php foreach ($members as $member): ?>
                                                    <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) ($item['assigned_user_id'] ?? '') === (string) $member['user_id'] ? 'selected' : '' ?>><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="field">
                                            <span>Posizione</span>
                                            <input type="number" name="position" value="<?= esc((string) $item['position']) ?>" min="0">
                                        </label>
                                        <label class="field field--full">
                                            <span>Note</span>
                                            <textarea name="notes" rows="2"><?= esc((string) ($item['notes'] ?? '')) ?></textarea>
                                        </label>
                                    </div>
                                    <div class="hero__actions">
                                        <button class="button button--primary" type="submit">Save item</button>
                                    </div>
                                </form>
                                <form method="post" action="<?= route_url('shopping.items.delete', $identifier, $item['id']) ?>" onsubmit="return confirm('Rimuovere questo item?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="shopping_list_id" value="<?= esc((string) $list['id']) ?>">
                                    <button class="button button--secondary" type="submit">Elimina item</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Purchased</p>
                <h2>Acquistati</h2>
            </div>
        </div>

        <?php if ($canManageShopping && $canCreateExpense && $purchasedItems !== []): ?>
            <form id="<?= esc($convertFormId) ?>" class="auth-form auth-form--compact shopping-convert-form" method="post" action="<?= route_url('shopping.convert', $identifier, $list['id']) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field field--full">
                        <span>Titolo expense</span>
                        <input type="text" name="title" value="<?= esc(old('title', (string) $defaults['title'])) ?>" required>
                    </label>
                    <label class="field">
                        <span>Totale</span>
                        <input type="number" name="total_amount" value="<?= esc(old('total_amount', '')) ?>" step="0.01" min="0.01" required>
                    </label>
                    <label class="field">
                        <span>Data spesa</span>
                        <input type="date" name="expense_date" value="<?= esc(old('expense_date', (string) $defaults['expense_date'])) ?>" required>
                    </label>
                    <label class="field">
                        <span>Pagatore</span>
                        <select name="payer_user_id">
                            <?php foreach ($members as $member): ?>
                                <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) old('payer_user_id', (string) $defaults['payer_user_id']) === (string) $member['user_id'] ? 'selected' : '' ?>><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Categoria expense</span>
                        <select name="category_id">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= esc((string) $category['id']) ?>" <?= (string) old('category_id', '') === (string) $category['id'] ? 'selected' : '' ?>><?= esc((string) $category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="field field--full">
                        <span>Partecipanti</span>
                        <div class="checkbox-grid">
                            <?php foreach ($members as $member): ?>
                                <label class="checkbox-row">
                                    <input type="checkbox" name="participant_user_ids[]" value="<?= esc((string) $member['user_id']) ?>" <?= in_array((int) $member['user_id'], array_map('intval', (array) old('participant_user_ids', $defaults['participant_user_ids'])), true) ? 'checked' : '' ?>>
                                    <span><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="hero__actions">
                    <button class="button button--primary" type="submit">Converti in expense</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="stack">
            <?php if ($purchasedItems === []): ?>
                <?php $title = 'Nessun item acquistato'; $message = 'Gli articoli marcati purchased finiscono qui, pronti per eventuale conversione in expense.'; $actionLabel = null; $actionHref = null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($purchasedItems as $item): ?>
                    <article class="row-card shopping-item-card">
                        <div class="shopping-item-card__header">
                            <?php if ($canManageShopping && $canCreateExpense && empty($item['converted_expense_id'])): ?>
                                <label class="checkbox-row">
                                    <input type="checkbox" name="item_ids[]" value="<?= esc((string) $item['id']) ?>" form="<?= esc($convertFormId) ?>">
                                    <span></span>
                                </label>
                            <?php endif; ?>
                            <div class="stack stack--compact">
                                <strong><?= esc((string) $item['name']) ?></strong>
                                <p><?= esc(shopping_quantity_label((string) $item['quantity'], $item['unit'] ?? null)) ?></p>
                            </div>
                            <?php if (! empty($item['converted_expense_id'])): ?>
                                <span class="badge badge--expense-active">Converted</span>
                            <?php else: ?>
                                <span class="badge badge--expense-step">Purchased</span>
                            <?php endif; ?>
                        </div>

                        <div class="expense-row__meta">
                            <?php if (! empty($item['purchased_by_name'])): ?>
                                <span class="badge badge--expense-step"><?= esc((string) $item['purchased_by_name']) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($item['purchased_at'])): ?>
                                <span class="badge badge--expense-step"><?= esc((string) $item['purchased_at']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($canManageShopping && empty($item['converted_expense_id'])): ?>
                            <div class="hero__actions">
                                <form method="post" action="<?= route_url('shopping.items.toggle', $identifier, $item['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="shopping_list_id" value="<?= esc((string) $list['id']) ?>">
                                    <button class="button button--secondary" type="submit">Riapri item</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
