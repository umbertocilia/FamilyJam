<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $recurringContext['membership'];
$household = $recurringContext['household'];
$identifier = (string) ($household['slug'] ?? $membership['household_slug'] ?? $household['id']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Recurring Expenses</p>
        <h1>Regole ricorrenti di <?= esc((string) $household['name']) ?></h1>
        <p class="hero__lead">Le recurring rules salvano un template expense in `config_json` e generano occorrenze reali tramite job idempotente CLI.</p>
    </div>

    <div class="hero__actions">
        <?php if ($recurringContext['canCreateRecurring']): ?>
            <a class="button button--primary" href="<?= route_url('recurring.create', $identifier) ?>">Nuova recurring expense</a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('expenses.index', $identifier) ?>">Vai alle spese</a>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <div>
            <p class="section-heading__eyebrow">Recurring rules</p>
            <h2>Elenco regole</h2>
        </div>
    </div>

    <div class="list-table">
        <?php if ($recurringContext['rules'] === []): ?>
            <div class="row-card row-card--vertical">
                <strong>Nessuna recurring expense configurata</strong>
                <p>Crea una regola per generare spese periodiche senza duplicazioni manuali.</p>
            </div>
        <?php else: ?>
            <?php foreach ($recurringContext['rules'] as $rule): ?>
                <?php $customUnit = $rule['schedule_config']['custom_unit'] ?? null; ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <div class="expense-row__header">
                            <strong><?= esc((string) ($rule['template']['title'] ?? 'Recurring expense')) ?></strong>
                            <span class="badge <?= ! empty($rule['is_active']) ? 'badge--expense-active' : 'badge--expense-deleted' ?>">
                                <?= ! empty($rule['is_active']) ? 'Active' : 'Disabled' ?>
                            </span>
                        </div>
                        <p>
                            <?= esc(recurring_frequency_label((string) $rule['frequency'], is_string($customUnit) ? $customUnit : null, (int) $rule['interval_value'])) ?>
                            - Next run: <?= esc((string) ($rule['next_run_at'] ?? 'n/a')) ?>
                        </p>
                        <div class="expense-row__meta">
                            <span class="badge badge--expense-split"><?= esc(expense_split_label((string) ($rule['template']['split_method'] ?? 'equal'))) ?></span>
                            <?php foreach (($rule['by_weekday_list'] ?? []) as $weekday): ?>
                                <span class="badge badge--expense-step"><?= esc(weekday_label((int) $weekday)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="list-table__meta">
                        <strong><?= esc(money_format((string) ($rule['template']['total_amount'] ?? '0.00'), (string) ($rule['template']['currency'] ?? $household['base_currency']))) ?></strong>
                        <small><?= esc((string) ($rule['created_by_name'] ?? $rule['created_by_email'] ?? '')) ?></small>
                        <div class="hero__actions">
                            <a class="button button--secondary" href="<?= route_url('recurring.edit', $identifier, $rule['id']) ?>">Modifica</a>
                            <?php if (! empty($rule['is_active'])): ?>
                                <form method="post" action="<?= route_url('recurring.disable', $identifier, $rule['id']) ?>" onsubmit="return confirm('Disattivare questa recurring expense?');">
                                    <?= csrf_field() ?>
                                    <button class="button button--secondary" type="submit">Disable</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
