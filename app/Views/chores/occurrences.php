<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $choreOccurrenceContext['membership'];
$members = $choreOccurrenceContext['members'];
$occurrences = $choreOccurrenceContext['occurrences'];
$filters = $choreOccurrenceContext['filters'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$currentUserId = (int) ($choreOccurrenceContext['currentUserId'] ?? 0);
$canManageChores = ! empty($choreOccurrenceContext['canManageChores']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Occurrences</p>
        <h1>Lista occorrenze chore</h1>
        <p class="hero__lead">Feed operativo unico per pending, overdue, completed e skipped.</p>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('chores.templates', $identifier) ?>">Templates</a>
        <a class="button button--secondary" href="<?= route_url('chores.my', $identifier) ?>">My chores</a>
    </div>
</section>

<section class="panel">
    <form class="auth-form auth-form--compact" method="get" action="<?= route_url('chores.occurrences', $identifier) ?>">
        <div class="form-grid">
            <label class="field">
                <span>Status</span>
                <select name="status">
                    <option value="">Tutti</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="skipped" <?= ($filters['status'] ?? '') === 'skipped' ? 'selected' : '' ?>>Skipped</option>
                </select>
            </label>
            <label class="field">
                <span>Assigned member</span>
                <select name="assigned_user_id">
                    <option value="">Tutti</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= esc((string) $member['user_id']) ?>" <?= (string) ($filters['assigned_user_id'] ?? '') === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= esc((string) ($member['display_name'] ?? $member['email'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="hero__actions">
            <button class="button button--primary" type="submit">Filtra</button>
            <a class="button button--secondary" href="<?= route_url('chores.occurrences', $identifier) ?>">Reset</a>
        </div>
    </form>

    <div class="list-table">
        <?php if ($occurrences === []): ?>
            <div class="row-card row-card--vertical">
                <strong>Nessuna occorrenza trovata</strong>
                <p>Genera una scadenza dai template oppure attendi il job recurring.</p>
            </div>
        <?php else: ?>
            <?php foreach ($occurrences as $occurrence): ?>
                <div class="list-table__row">
                    <div class="stack stack--compact">
                        <div class="expense-row__header">
                            <strong><?= esc((string) $occurrence['chore_title']) ?></strong>
                            <span class="badge <?= esc(chore_status_badge_class((string) $occurrence['status'])) ?>"><?= esc(chore_status_label((string) $occurrence['status'])) ?></span>
                        </div>
                        <p><?= esc((string) $occurrence['due_at']) ?> - <?= esc((string) ($occurrence['assigned_user_name'] ?? 'Unassigned')) ?></p>
                        <?php if (! empty($occurrence['skip_reason'])): ?>
                            <p>Skip reason: <?= esc((string) $occurrence['skip_reason']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="list-table__meta chore-actions">
                        <?php if (in_array((string) $occurrence['status'], ['pending', 'overdue'], true) && ($canManageChores || (int) ($occurrence['assigned_user_id'] ?? 0) === $currentUserId)): ?>
                            <form method="post" action="<?= route_url('chores.complete', $identifier, $occurrence['id']) ?>">
                                <?= csrf_field() ?>
                                <button class="button button--primary" type="submit">Complete</button>
                            </form>
                            <form class="chore-inline-form" method="post" action="<?= route_url('chores.skip', $identifier, $occurrence['id']) ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="skip_reason" placeholder="Motivazione skip" required>
                                <button class="button button--secondary" type="submit">Skip</button>
                            </form>
                        <?php else: ?>
                            <span class="badge badge--expense-step"><?= esc((string) $occurrence['points_awarded']) ?> pts</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
