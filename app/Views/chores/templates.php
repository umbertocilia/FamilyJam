<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $choreTemplateContext['membership'];
$templates = $choreTemplateContext['templates'];
$filters = $choreTemplateContext['filters'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManageChores = ! empty($choreTemplateContext['canManageChores']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Chore Templates</p>
        <h1>Template faccende</h1>
        <p class="hero__lead">Pianifica chores ricorrenti o manuali con assegnazione fissa, rotazione e generazione one-off.</p>
    </div>
    <div class="hero__actions">
        <?php if ($canManageChores): ?>
            <a class="button button--primary" href="<?= route_url('chores.create', $identifier) ?>">Nuovo template</a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('chores.index', $identifier) ?>">Overview</a>
    </div>
</section>

<section class="panel">
    <form class="auth-form auth-form--compact" method="get" action="<?= route_url('chores.templates', $identifier) ?>">
        <div class="form-grid">
            <label class="field">
                <span>Assignment mode</span>
                <select name="assignment_mode">
                    <option value="">Tutti</option>
                    <option value="fixed" <?= ($filters['assignment_mode'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed</option>
                    <option value="rotation" <?= ($filters['assignment_mode'] ?? '') === 'rotation' ? 'selected' : '' ?>>Rotation</option>
                </select>
            </label>
            <label class="field">
                <span>Status</span>
                <select name="is_active">
                    <option value="">Tutti</option>
                    <option value="1" <?= (string) ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (string) ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Disabled</option>
                </select>
            </label>
        </div>
        <div class="hero__actions">
            <button class="button button--primary" type="submit">Filtra</button>
            <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>">Reset</a>
        </div>
    </form>

    <div class="list-table">
        <?php if ($templates === []): ?>
            <div class="row-card row-card--vertical">
                <strong>Nessun template trovato</strong>
                <p>Crea la prima faccenda oppure rimuovi i filtri attivi.</p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <div class="expense-row__header">
                            <strong><?= esc((string) $template['title']) ?></strong>
                            <span class="badge <?= (int) $template['is_active'] === 1 ? 'badge--expense-active' : 'badge--expense-deleted' ?>">
                                <?= (int) $template['is_active'] === 1 ? 'Active' : 'Disabled' ?>
                            </span>
                        </div>
                        <p><?= esc((string) ($template['description'] ?? 'No description')) ?></p>
                        <div class="expense-row__meta">
                            <span class="badge badge--expense-step"><?= esc(chore_assignment_label((string) $template['assignment_mode'])) ?></span>
                            <span class="badge badge--expense-step"><?= esc((string) $template['estimated_minutes']) ?> min</span>
                            <span class="badge badge--expense-step"><?= esc((string) $template['points']) ?> pts</span>
                            <?php if (is_array($template['recurring'] ?? null)): ?>
                                <span class="badge badge--expense-split"><?= esc(recurring_frequency_label((string) $template['recurring']['frequency'], $template['recurring']['custom_unit'] ?? null, (int) ($template['recurring']['interval_value'] ?? 1))) ?></span>
                            <?php else: ?>
                                <span class="badge badge--expense-step">Manual only</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canManageChores): ?>
                        <div class="list-table__meta chore-actions">
                            <a class="button button--secondary" href="<?= route_url('chores.edit', $identifier, $template['id']) ?>">Modifica</a>
                            <form method="post" action="<?= route_url('chores.toggle', $identifier, $template['id']) ?>">
                                <?= csrf_field() ?>
                                <button class="button button--secondary" type="submit"><?= (int) $template['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                            </form>
                            <form class="chore-inline-form" method="post" action="<?= route_url('chores.occurrence.create', $identifier, $template['id']) ?>">
                                <?= csrf_field() ?>
                                <input type="datetime-local" name="due_at" value="<?= esc(date('Y-m-d\TH:i', strtotime('+1 day'))) ?>">
                                <button class="button button--primary" type="submit">Genera</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
