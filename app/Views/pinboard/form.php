<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $pinboardFormContext['membership'];
$post = is_array($pinboardFormContext['post'] ?? null) ? $pinboardFormContext['post'] : [];
$attachments = $pinboardFormContext['attachments'] ?? [];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$action = $isEdit ? route_url('pinboard.update', $identifier, $post['id']) : route_url('pinboard.store', $identifier);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Pinboard</p>
        <h1><?= $isEdit ? 'Modifica post' : 'New post' ?></h1>
        <p class="hero__lead">Titolo chiaro, contenuto diretto, tipo post e pin opzionale. Allegato singolo opzionale, senza complicare il flusso.</p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span>Titolo</span>
                <input class="<?= esc(field_error_class($formErrors, 'title')) ?>" type="text" name="title" value="<?= esc(old('title', (string) ($post['title'] ?? ''))) ?>" maxlength="160" required>
                <?php if (field_error($formErrors, 'title') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'title')) ?></small>
                <?php endif; ?>
            </label>
            <label class="field">
                <span>Tipo post</span>
                <select name="post_type">
                    <?php foreach (['note', 'announcement', 'todo'] as $postType): ?>
                        <option value="<?= esc($postType) ?>" <?= old('post_type', (string) ($post['post_type'] ?? 'note')) === $postType ? 'selected' : '' ?>><?= esc(pinboard_post_type_label($postType)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Scadenza</span>
                <input type="datetime-local" name="due_at" value="<?= esc(old('due_at', ! empty($post['due_at']) ? date('Y-m-d\TH:i', strtotime((string) $post['due_at'])) : '')) ?>">
            </label>
            <label class="field field--full">
                <span>Contenuto</span>
                <textarea class="<?= esc(field_error_class($formErrors, 'body')) ?>" name="body" rows="8" required><?= esc(old('body', (string) ($post['body'] ?? ''))) ?></textarea>
                <?php if (field_error($formErrors, 'body') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'body')) ?></small>
                <?php endif; ?>
            </label>
            <label class="field field--full">
                <span>Allegato opzionale</span>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <?php if ($attachments !== []): ?>
                    <small class="field-hint">Allegati gia presenti: <?= esc((string) count($attachments)) ?></small>
                <?php endif; ?>
            </label>
        </div>

        <label class="checkbox-row">
            <input type="checkbox" name="is_pinned" value="1" <?= old('is_pinned', (string) ($post['is_pinned'] ?? '0')) ? 'checked' : '' ?>>
            <span>Tieni questo post in alto nella bacheca</span>
        </label>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Salva post' : 'Pubblica post' ?></button>
            <a class="button button--secondary" href="<?= route_url('pinboard.index', $identifier) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
