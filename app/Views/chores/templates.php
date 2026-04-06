<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $choreTemplateContext['membership'];
$templates = $choreTemplateContext['templates'];
$filters = $choreTemplateContext['filters'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManageChores = ! empty($choreTemplateContext['canManageChores']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Template faccende' : 'Chore templates') ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Template faccende' : 'Chore templates') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Pianifica faccende ricorrenti o manuali con assegnazione fissa, rotazione e generazione one-off.' : 'Plan recurring or manual chores with fixed assignee, rotation and one-off generation.') ?></p>
    </div>
    <div class="hero__actions">
        <?php if ($canManageChores): ?>
            <a class="button button--primary" href="<?= route_url('chores.create', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Nuovo template' : 'New template') ?></a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('chores.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Panoramica' : 'Overview') ?></a>
    </div>
</section>

<section class="panel">
    <form class="auth-form auth-form--compact" method="get" action="<?= route_url('chores.templates', $identifier) ?>">
        <div class="form-grid">
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Modalita assegnazione' : 'Assignment mode') ?></span>
                <select name="assignment_mode">
                    <option value=""><?= esc(ui_locale() === 'it' ? 'Tutti' : 'All') ?></option>
                    <option value="fixed" <?= ($filters['assignment_mode'] ?? '') === 'fixed' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Fissa' : 'Fixed') ?></option>
                    <option value="rotation" <?= ($filters['assignment_mode'] ?? '') === 'rotation' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Rotazione' : 'Rotation') ?></option>
                </select>
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Stato' : 'Status') ?></span>
                <select name="is_active">
                    <option value=""><?= esc(ui_locale() === 'it' ? 'Tutti' : 'All') ?></option>
                    <option value="1" <?= (string) ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Attivo' : 'Active') ?></option>
                    <option value="0" <?= (string) ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>><?= esc(ui_locale() === 'it' ? 'Disattivato' : 'Disabled') ?></option>
                </select>
            </label>
        </div>
        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Filtra' : 'Filter') ?></button>
            <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Reset' : 'Reset') ?></a>
        </div>
    </form>

    <div class="list-table">
        <?php if ($templates === []): ?>
            <div class="row-card row-card--vertical">
                <strong><?= esc(ui_locale() === 'it' ? 'Nessun template trovato' : 'No templates found') ?></strong>
                <p><?= esc(ui_locale() === 'it' ? 'Crea la prima faccenda oppure rimuovi i filtri attivi.' : 'Create the first chore or clear the active filters.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <div class="expense-row__header">
                            <strong><?= esc((string) $template['title']) ?></strong>
                            <span class="badge <?= (int) $template['is_active'] === 1 ? 'badge--expense-active' : 'badge--expense-deleted' ?>">
                                <?= esc((int) $template['is_active'] === 1 ? (ui_locale() === 'it' ? 'Attivo' : 'Active') : (ui_locale() === 'it' ? 'Disattivato' : 'Disabled')) ?>
                            </span>
                        </div>
                        <p><?= esc((string) ($template['description'] ?? (ui_locale() === 'it' ? 'Nessuna descrizione' : 'No description'))) ?></p>
                        <div class="expense-row__meta">
                            <span class="badge badge--expense-step"><?= esc(chore_assignment_label((string) $template['assignment_mode'])) ?></span>
                            <span class="badge badge--expense-step"><?= esc((string) $template['estimated_minutes']) ?> min</span>
                            <span class="badge badge--expense-step"><?= esc((string) $template['points']) ?> pts</span>
                            <?php if (is_array($template['recurring'] ?? null)): ?>
                                <span class="badge badge--expense-split"><?= esc(recurring_frequency_label((string) $template['recurring']['frequency'], $template['recurring']['custom_unit'] ?? null, (int) ($template['recurring']['interval_value'] ?? 1))) ?></span>
                            <?php else: ?>
                                <span class="badge badge--expense-step"><?= esc(ui_locale() === 'it' ? 'Solo manuale' : 'Manual only') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canManageChores): ?>
                        <div class="list-table__meta chore-actions">
                            <a class="button button--secondary" href="<?= route_url('chores.edit', $identifier, $template['id']) ?>"><?= esc(ui_locale() === 'it' ? 'Modifica' : 'Edit') ?></a>
                            <form method="post" action="<?= route_url('chores.toggle', $identifier, $template['id']) ?>">
                                <?= csrf_field() ?>
                                <button class="button button--secondary" type="submit"><?= esc((int) $template['is_active'] === 1 ? (ui_locale() === 'it' ? 'Disattiva' : 'Disable') : (ui_locale() === 'it' ? 'Attiva' : 'Enable')) ?></button>
                            </form>
                            <form class="chore-inline-form" method="post" action="<?= route_url('chores.occurrence.create', $identifier, $template['id']) ?>">
                                <?= csrf_field() ?>
                                <input type="datetime-local" name="due_at" value="<?= esc(date('Y-m-d\TH:i', strtotime('+1 day'))) ?>">
                                <button class="button button--primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Genera' : 'Generate') ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
