<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $pinboardIndexContext['membership'];
$posts = $pinboardIndexContext['posts'];
$summary = $pinboardIndexContext['summary'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManagePinboard = ! empty($pinboardIndexContext['canManagePinboard']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_text('nav.pinboard')) ?></p>
        <h1><?= esc(ui_locale() === 'it' ? 'Bacheca condivisa' : 'Shared pinboard') ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Note, annunci e promemoria household con pinned in alto e azioni leggibili anche da mobile.' : 'Notes, announcements and household reminders with pinned items first and actions that stay readable on mobile.') ?></p>
        <div class="quick-filter-bar" aria-label="Pinboard shortcuts">
            <a class="module-chip" href="<?= route_url('pinboard.index', $identifier) ?>">All posts</a>
            <?php if ($canManagePinboard): ?>
                <a class="module-chip" href="<?= route_url('pinboard.create', $identifier) ?>">New post</a>
            <?php endif; ?>
            <a class="module-chip" href="<?= route_url('notifications.index', $identifier) ?>">Notifications</a>
        </div>
    </div>
    <div class="hero__actions">
        <?php if ($canManagePinboard): ?>
            <a class="button button--primary" href="<?= route_url('pinboard.create', $identifier) ?>">New post</a>
        <?php endif; ?>
    </div>
</section>

<section class="summary-grid">
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Post' : 'Posts') ?></span><strong><?= esc((string) $summary['posts']) ?></strong></article>
    <article class="metric-card"><span>Pinned</span><strong><?= esc((string) $summary['pinned']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Scadenze' : 'Due items') ?></span><strong><?= esc((string) $summary['due']) ?></strong></article>
    <article class="metric-card"><span><?= esc(ui_locale() === 'it' ? 'Commenti' : 'Comments') ?></span><strong><?= esc((string) $summary['comments']) ?></strong></article>
</section>

<section class="content-grid">
    <?php if ($posts === []): ?>
        <article class="panel">
            <?php $title = ui_locale() === 'it' ? 'Nessun post in bacheca' : 'No pinboard posts yet'; $message = ui_locale() === 'it' ? 'Crea il primo annuncio o nota per iniziare il thread condiviso.' : 'Create the first announcement or note to start the shared thread.'; $actionLabel = $canManagePinboard ? (ui_locale() === 'it' ? 'Nuovo post' : 'New post') : null; $actionHref = $canManagePinboard ? route_url('pinboard.create', $identifier) : null; $icon = 'fas fa-thumbtack'; ?>
            <?= $this->include('partials/components/empty_state') ?>
        </article>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <article class="panel pinboard-card">
                <div class="expense-row__header">
                    <div class="stack stack--compact">
                        <strong><?= esc((string) $post['title']) ?></strong>
                        <p><?= esc((string) ($post['author_name'] ?? $post['author_email'] ?? 'FamilyJam')) ?></p>
                    </div>
                    <?php if ((int) ($post['is_pinned'] ?? 0) === 1): ?>
                        <span class="badge badge--expense-active">Pinned</span>
                    <?php endif; ?>
                </div>

                <p><?= esc(character_limiter(strip_tags((string) $post['body']), 180)) ?></p>

                <div class="expense-row__meta">
                    <span class="badge <?= esc(pinboard_post_type_badge_class((string) $post['post_type'])) ?>"><?= esc(pinboard_post_type_label((string) $post['post_type'])) ?></span>
                    <span class="badge badge--expense-step"><?= esc((string) ($post['comments_count'] ?? 0)) ?> comments</span>
                    <?php if (! empty($post['due_at'])): ?>
                        <span class="badge badge--shopping-open">Due <?= esc((string) $post['due_at']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="hero__actions">
                    <a class="button button--primary" href="<?= route_url('pinboard.show', $identifier, $post['id']) ?>">Open thread</a>
                    <?php if ($canManagePinboard): ?>
                        <a class="button button--secondary" href="<?= route_url('pinboard.edit', $identifier, $post['id']) ?>">Edit</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
