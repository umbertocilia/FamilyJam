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
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$activeCurrency = is_array($activeHousehold ?? null) ? (string) ($activeHousehold['base_currency'] ?? 'EUR') : 'EUR';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h1><?= esc(ui_text('nav.expenses')) ?></h1>
    </div>
    <div class="col-sm-6">
        <div class="float-sm-right">
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
