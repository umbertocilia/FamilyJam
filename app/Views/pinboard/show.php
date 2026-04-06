<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $pinboardDetailContext['membership'];
$post = $pinboardDetailContext['post'];
$comments = $pinboardDetailContext['comments'];
$attachments = $pinboardDetailContext['attachments'];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$canManagePinboard = ! empty($pinboardDetailContext['canManagePinboard']);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Pinboard Post</p>
        <h1><?= esc((string) $post['title']) ?></h1>
        <p class="hero__lead"><?= esc((string) ($post['author_name'] ?? $post['author_email'] ?? 'FamilyJam')) ?> / <?= esc(pinboard_post_type_label((string) $post['post_type'])) ?></p>
        <div class="quick-filter-bar" aria-label="Meta post">
            <?php if ((int) ($post['is_pinned'] ?? 0) === 1): ?>
                <span class="badge badge--expense-active">Pinned</span>
            <?php endif; ?>
            <span class="badge <?= esc(pinboard_post_type_badge_class((string) $post['post_type'])) ?>"><?= esc(pinboard_post_type_label((string) $post['post_type'])) ?></span>
            <?php if (! empty($post['due_at'])): ?>
                <span class="badge badge--shopping-open">Due <?= esc((string) $post['due_at']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('pinboard.index', $identifier) ?>">Torna alla bacheca</a>
        <?php if ($canManagePinboard): ?>
            <form method="post" action="<?= route_url('pinboard.pin', $identifier, $post['id']) ?>">
                <?= csrf_field() ?>
                <button class="button button--secondary" type="submit"><?= (int) ($post['is_pinned'] ?? 0) === 1 ? 'Unpin' : 'Pin' ?></button>
            </form>
            <a class="button button--secondary" href="<?= route_url('pinboard.edit', $identifier, $post['id']) ?>">Modifica</a>
            <form method="post" action="<?= route_url('pinboard.delete', $identifier, $post['id']) ?>" onsubmit="return confirm('Eliminare questo post?');">
                <?= csrf_field() ?>
                <button class="button button--secondary" type="submit">Elimina</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="content-grid content-grid--wide">
    <article class="panel">
        <div class="prose-block">
            <?= nl2br(esc((string) $post['body'])) ?>
        </div>

        <?php if ($attachments !== []): ?>
            <div class="stack">
                <h2>Allegati</h2>
                <?php foreach ($attachments as $attachment): ?>
                    <a class="row-card" href="<?= route_url('pinboard.attachment', $identifier, $post['id'], $attachment['id']) ?>">
                        <strong><?= esc((string) $attachment['original_name']) ?></strong>
                        <span><?= esc((string) $attachment['mime_type']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel pinboard-thread">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Comments</p>
                <h2>Thread</h2>
            </div>
        </div>

        <form class="auth-form auth-form--compact" method="post" action="<?= route_url('pinboard.comments.store', $identifier, $post['id']) ?>">
            <?= csrf_field() ?>
            <label class="field field--full">
                <span>Aggiungi commento</span>
                <textarea class="<?= esc(field_error_class($formErrors, 'body')) ?>" name="body" rows="3" required><?= esc(old('body', '')) ?></textarea>
                <?php if (field_error($formErrors, 'body') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'body')) ?></small>
                <?php endif; ?>
            </label>
            <div class="hero__actions">
                <button class="button button--primary" type="submit">Commenta</button>
            </div>
        </form>

        <div class="stack">
            <?php if ($comments === []): ?>
                <?php $title = 'Nessun commento'; $message = 'Il thread e ancora vuoto. Puoi aprirlo con il primo commento.'; $actionLabel = null; $actionHref = null; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <article class="pinboard-comment">
                        <div class="expense-row__header">
                            <strong><?= esc((string) ($comment['author_name'] ?? $comment['author_email'] ?? 'FamilyJam')) ?></strong>
                            <span><?= esc((string) $comment['created_at']) ?></span>
                        </div>
                        <p><?= nl2br(esc((string) $comment['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
