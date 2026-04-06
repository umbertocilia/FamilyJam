<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $expenseListContext['membership'];
$filters = $expenseListContext['filters'];
$expenses = $expenseListContext['expenses'];
$categories = $expenseListContext['categories'];
$expenseGroups = $expenseListContext['expenseGroups'];
$members = $expenseListContext['members'];
$availableMembers = $expenseListContext['availableMembers'] ?? [];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$activeCurrency = is_array($activeHousehold ?? null) ? (string) ($activeHousehold['base_currency'] ?? 'EUR') : 'EUR';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h1><?= esc(ui_text('nav.expenses')) ?></h1>
    </div>
    <div class="col-sm-6">
        <div class="float-sm-right">
            <a class="btn btn-default mr-2" href="<?= route_url('expenses.index', $identifier) ?>#expense-groups">
                <i class="fas fa-layer-group mr-1"></i><?= esc(ui_locale() === 'it' ? 'Gruppi spesa' : 'Expense groups') ?>
            </a>
            <?php if ($canCreateExpense): ?>
                <a class="btn btn-primary" href="<?= route_url('expenses.create', $identifier) ?>">
                    <i class="fas fa-plus mr-1"></i><?= esc(ui_locale() === 'it' ? 'Nuova spesa' : 'New expense') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= esc((string) count($expenses)) ?></h3>
                <p><?= esc(ui_locale() === 'it' ? 'Spese visibili' : 'Visible expenses') ?></p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= esc((string) ($membership['base_currency'] ?? $activeCurrency)) ?></h3>
                <p><?= esc(ui_locale() === 'it' ? 'Valuta casa' : 'Household currency') ?></p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= esc((string) (($filters['month'] ?? '') !== '' ? $filters['month'] : ui_text('common.all'))) ?></h3>
                <p><?= esc(ui_locale() === 'it' ? 'Filtro mese' : 'Month filter') ?></p>
            </div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= esc((string) count($members)) ?></h3>
                <p><?= esc(ui_locale() === 'it' ? 'Membri disponibili' : 'Available members') ?></p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i><?= esc(ui_locale() === 'it' ? 'Filtri rapidi' : 'Quick filters') ?></h3>
    </div>
    <form method="get" action="<?= route_url('expenses.index', $identifier) ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= esc(ui_locale() === 'it' ? 'Categoria' : 'Category') ?></label>
                        <select name="category_id" class="form-control">
                            <option value=""><?= esc(ui_text('common.all')) ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= esc((string) $category['id']) ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>>
                                    <?= esc((string) $category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= esc(ui_text('expense.label.group')) ?></label>
                        <select name="expense_group_id" class="form-control">
                            <option value=""><?= esc(ui_text('common.all')) ?></option>
                            <?php foreach ($expenseGroups as $group): ?>
                                <option value="<?= esc((string) $group['id']) ?>" <?= (string) ($filters['expense_group_id'] ?? '') === (string) $group['id'] ? 'selected' : '' ?>>
                                    <?= esc((string) $group['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= esc(ui_locale() === 'it' ? 'Mese' : 'Month') ?></label>
                        <input type="month" class="form-control" name="month" value="<?= esc((string) ($filters['month'] ?? '')) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= esc(ui_locale() === 'it' ? 'Membro' : 'Member') ?></label>
                        <select name="member_id" class="form-control">
                            <option value=""><?= esc(ui_text('common.all')) ?></option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) ($filters['member_id'] ?? '') === (string) $member['user_id'] ? 'selected' : '' ?>>
                                    <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?= esc(ui_locale() === 'it' ? 'Stato' : 'Status') ?></label>
                        <select name="status" class="form-control">
                            <option value=""><?= esc(ui_locale() === 'it' ? 'Attive e modificate' : 'Active and edited') ?></option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Attiva' : 'Active') ?></option>
                            <option value="edited" <?= ($filters['status'] ?? '') === 'edited' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Modificata' : 'Edited') ?></option>
                            <option value="deleted" <?= ($filters['status'] ?? '') === 'deleted' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Eliminata' : 'Deleted') ?></option>
                            <option value="disputed" <?= ($filters['status'] ?? '') === 'disputed' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Contestata' : 'Disputed') ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Applica filtri' : 'Apply filters') ?></button>
            <a class="btn btn-default ml-2" href="<?= route_url('expenses.index', $identifier) ?>"><?= esc(ui_text('common.reset')) ?></a>
        </div>
    </form>
</div>

<div class="card card-outline card-secondary" id="expense-groups">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i><?= esc(ui_locale() === 'it' ? 'Gruppi spesa' : 'Expense groups') ?></h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc(ui_locale() === 'it' ? 'Nuovo gruppo' : 'New group') ?></h3>
                    </div>
                    <form method="post" action="<?= route_url('expenses.groups.create', $identifier) ?>">
                        <?= csrf_field() ?>
                        <div class="card-body">
                            <div class="form-group">
                                <label><?= esc(ui_locale() === 'it' ? 'Nome gruppo' : 'Group name') ?></label>
                                <input class="form-control" type="text" name="name" value="<?= esc((string) old('name', '')) ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?= esc(ui_locale() === 'it' ? 'Descrizione' : 'Description') ?></label>
                                <input class="form-control" type="text" name="description" value="<?= esc((string) old('description', '')) ?>">
                            </div>
                            <div class="form-group">
                                <label><?= esc(ui_locale() === 'it' ? 'Colore' : 'Color') ?></label>
                                <input class="form-control" type="color" name="color" value="<?= esc((string) old('color', '#17a2b8')) ?>">
                            </div>
                            <div class="form-group">
                                <label><?= esc(ui_locale() === 'it' ? 'Membri del gruppo' : 'Group members') ?></label>
                                <div class="d-flex flex-column">
                                    <?php $selectedMembers = array_map('strval', (array) (old('member_user_ids') ?? [])); ?>
                                    <?php foreach ($availableMembers as $member): ?>
                                        <label class="mb-2 font-weight-normal">
                                            <input type="checkbox" name="member_user_ids[]" value="<?= esc((string) $member['user_id']) ?>" <?= in_array((string) $member['user_id'], $selectedMembers, true) ? 'checked' : '' ?>>
                                            <span class="ml-2"><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-plus mr-1"></i><?= esc(ui_locale() === 'it' ? 'Crea gruppo' : 'Create group') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if ($expenseGroups === []): ?>
                    <div class="callout callout-info mb-0">
                        <h5><?= esc(ui_locale() === 'it' ? 'Nessun gruppo spesa' : 'No expense groups yet') ?></h5>
                        <p><?= esc(ui_locale() === 'it'
                            ? 'Crea gruppi come Utenze, Spesa o Viaggio e inserisci le spese direttamente al loro interno.'
                            : 'Create groups such as Utilities, Groceries or Travel and file expenses directly inside them.') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($expenseGroups as $group): ?>
                        <div class="card card-outline mb-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-folder-open mr-2" style="color: <?= esc((string) ($group['color'] ?? '#6c757d')) ?>;"></i>
                                    <?= esc((string) $group['name']) ?>
                                </h3>
                                <div class="card-tools">
                                    <a class="btn btn-tool" href="<?= route_url('expenses.index', $identifier) ?>?expense_group_id=<?= esc((string) $group['id']) ?>">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                    <a class="btn btn-tool" href="<?= route_url('balances.overview', $identifier) ?>#expense-group-<?= esc((string) $group['id']) ?>">
                                        <i class="fas fa-balance-scale"></i>
                                    </a>
                                    <?php if (has_permission('add_settlement', $activeHousehold, $currentUserId)): ?>
                                        <a class="btn btn-tool" href="<?= route_url('settlements.create', $identifier) ?>?expense_group_id=<?= esc((string) $group['id']) ?>">
                                            <i class="fas fa-hand-holding-usd"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (! empty($group['description'])): ?>
                                    <p class="text-muted"><?= esc((string) $group['description']) ?></p>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <?php foreach (($group['members'] ?? []) as $member): ?>
                                        <span class="badge badge-light border mr-1 mb-1"><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mb-3">
                                    <?php if ($canCreateExpense): ?>
                                        <a class="btn btn-sm btn-primary mr-2" href="<?= route_url('expenses.create', $identifier) ?>?expense_group_id=<?= esc((string) $group['id']) ?>">
                                            <i class="fas fa-plus mr-1"></i><?= esc(ui_locale() === 'it' ? 'Nuova spesa nel gruppo' : 'Add expense to group') ?>
                                        </a>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-default mr-2" href="<?= route_url('expenses.index', $identifier) ?>?expense_group_id=<?= esc((string) $group['id']) ?>">
                                        <i class="fas fa-list mr-1"></i><?= esc(ui_locale() === 'it' ? 'Vedi spese del gruppo' : 'View group expenses') ?>
                                    </a>
                                    <a class="btn btn-sm btn-default mr-2" href="<?= route_url('balances.overview', $identifier) ?>#expense-group-<?= esc((string) $group['id']) ?>">
                                        <i class="fas fa-wallet mr-1"></i><?= esc(ui_locale() === 'it' ? 'Saldi del gruppo' : 'Group balances') ?>
                                    </a>
                                    <a class="btn btn-sm btn-success" href="<?= route_url('balances.overview', $identifier) ?>#expense-group-<?= esc((string) $group['id']) ?>">
                                        <i class="fas fa-check-circle mr-1"></i><?= esc(ui_locale() === 'it' ? 'Pareggia saldi nel gruppo' : 'Open balances and settle') ?>
                                    </a>
                                </div>

                                <form method="post" action="<?= route_url('expenses.groups.update', $identifier, $group['id']) ?>">
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label><?= esc(ui_locale() === 'it' ? 'Nome' : 'Name') ?></label>
                                                <input class="form-control" type="text" name="name" value="<?= esc((string) $group['name']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label><?= esc(ui_locale() === 'it' ? 'Descrizione' : 'Description') ?></label>
                                                <input class="form-control" type="text" name="description" value="<?= esc((string) ($group['description'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label><?= esc(ui_locale() === 'it' ? 'Colore' : 'Color') ?></label>
                                                <input class="form-control" type="color" name="color" value="<?= esc((string) ($group['color'] ?? '#17a2b8')) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><?= esc(ui_locale() === 'it' ? 'Membri del gruppo' : 'Group members') ?></label>
                                        <div class="row">
                                            <?php $groupMemberIds = array_map(static fn ($value): int => (int) $value, $group['member_user_ids'] ?? []); ?>
                                            <?php foreach ($availableMembers as $member): ?>
                                                <div class="col-md-6 col-xl-4">
                                                    <label class="mb-2 font-weight-normal">
                                                        <input
                                                            type="checkbox"
                                                            name="member_user_ids[]"
                                                            value="<?= esc((string) $member['user_id']) ?>"
                                                            <?= in_array((int) $member['user_id'], $groupMemberIds, true) ? 'checked' : '' ?>
                                                        >
                                                        <span class="ml-2"><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center">
                                        <button class="btn btn-primary btn-sm mr-2 mb-2" type="submit">
                                            <i class="fas fa-save mr-1"></i><?= esc(ui_locale() === 'it' ? 'Aggiorna gruppo' : 'Update group') ?>
                                        </button>
                                    </div>
                                </form>
                                <form method="post" action="<?= route_url('expenses.groups.delete', $identifier, $group['id']) ?>" class="mb-2">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm" type="submit">
                                        <i class="fas fa-trash mr-1"></i><?= esc(ui_locale() === 'it' ? 'Elimina gruppo' : 'Delete group') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-stream mr-2"></i><?= esc(ui_locale() === 'it' ? 'Registro spese' : 'Expense ledger') ?></h3>
    </div>
    <div class="card-body table-responsive p-0">
        <?php if ($expenses === []): ?>
            <div class="p-4">
                <?php
                $title = ui_locale() === 'it' ? 'Nessuna spesa trovata' : 'No expenses found';
                $message = ui_locale() === 'it' ? 'Crea la prima spesa o amplia i filtri per vedere lo storico disponibile.' : 'Create the first expense or widen the filters to view the available history.';
                $actionLabel = $canCreateExpense ? (ui_locale() === 'it' ? 'Nuova spesa' : 'New expense') : null;
                $actionHref = $canCreateExpense ? route_url('expenses.create', $identifier) : null;
                $icon = 'fas fa-receipt';
                ?>
                <?= $this->include('partials/components/empty_state') ?>
            </div>
        <?php else: ?>
            <table class="table table-hover text-nowrap">
                <thead>
                <tr>
                    <th><?= esc(ui_locale() === 'it' ? 'Titolo' : 'Title') ?></th>
                    <th><?= esc(ui_locale() === 'it' ? 'Categoria' : 'Category') ?></th>
                    <th><?= esc(ui_text('expense.label.group')) ?></th>
                    <th><?= esc(ui_locale() === 'it' ? 'Data' : 'Date') ?></th>
                    <th><?= esc(ui_locale() === 'it' ? 'Ripartizione' : 'Split') ?></th>
                    <th><?= esc(ui_locale() === 'it' ? 'Stato' : 'Status') ?></th>
                    <th class="text-right"><?= esc(ui_locale() === 'it' ? 'Importo' : 'Amount') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td>
                            <a href="<?= route_url('expenses.show', $identifier, $expense['id']) ?>"><?= esc((string) $expense['title']) ?></a>
                            <div class="text-muted text-sm"><?= esc((string) ($expense['created_by_name'] ?? '')) ?></div>
                        </td>
                        <td><?= esc((string) ($expense['category_name'] ?? ui_text('category.uncategorized'))) ?></td>
                        <td><?= esc((string) (($expense['expense_group_name'] ?? '') !== '' ? $expense['expense_group_name'] : ui_text('expense.label.group.general'))) ?></td>
                        <td><?= esc((string) $expense['expense_date']) ?></td>
                        <td><span class="badge badge-info"><?= esc(expense_split_label((string) $expense['split_method'])) ?></span></td>
                        <td><span class="badge <?= esc(expense_status_badge_class((string) $expense['status'])) ?>"><?= esc(expense_status_label((string) $expense['status'])) ?></span></td>
                        <td class="text-right"><?= esc(money_format((string) $expense['total_amount'], (string) $expense['currency'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
