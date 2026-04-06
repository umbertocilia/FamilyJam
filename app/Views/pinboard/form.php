<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $pinboardFormContext['membership'];
$post = is_array($pinboardFormContext['post'] ?? null) ? $pinboardFormContext['post'] : [];
$attachments = $pinboardFormContext['attachments'] ?? [];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$action = $isEdit ? route_url('pinboard.update', $identifier, $post['id']) : route_url('pinboard.store', $identifier);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Bacheca' : 'Pinboard') ?></p>
        <h1><?= esc($isEdit ? (ui_locale() === 'it' ? 'Modifica post' : 'Edit post') : (ui_locale() === 'it' ? 'Nuovo post' : 'New post')) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Titolo chiaro, contenuto diretto, tipo post e pin opzionale. Allegato singolo opzionale, senza complicare il flusso.' : 'Clear title, direct content, post type and optional pin. Single optional attachment, without complicating the flow.') ?></p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Titolo' : 'Title') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'title')) ?>" type="text" name="title" value="<?= esc(old('title', (string) ($post['title'] ?? ''))) ?>" maxlength="160" required>
                <?php if (field_error($formErrors, 'title') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'title')) ?></small>
                <?php endif; ?>
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Tipo post' : 'Post type') ?></span>
                <select name="post_type">
                    <?php foreach (['note', 'announcement', 'todo'] as $postType): ?>
                        <option value="<?= esc($postType) ?>" <?= old('post_type', (string) ($post['post_type'] ?? 'note')) === $postType ? 'selected' : '' ?>><?= esc(pinboard_post_type_label($postType)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span><?= esc(ui_locale() === 'it' ? 'Scadenza' : 'Due at') ?></span>
                <input type="datetime-local" name="due_at" value="<?= esc(old('due_at', ! empty($post['due_at']) ? date('Y-m-d\TH:i', strtotime((string) $post['due_at'])) : '')) ?>">
            </label>
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Contenuto' : 'Body') ?></span>
                <textarea class="<?= esc(field_error_class($formErrors, 'body')) ?>" name="body" rows="8" required><?= esc(old('body', (string) ($post['body'] ?? ''))) ?></textarea>
                <?php if (field_error($formErrors, 'body') !== null): ?>
                    <small class="field__error"><?= esc((string) field_error($formErrors, 'body')) ?></small>
                <?php endif; ?>
            </label>
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Allegato opzionale' : 'Optional attachment') ?></span>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <?php if ($attachments !== []): ?>
                    <small class="field-hint"><?= esc(ui_locale() === 'it' ? 'Allegati gia presenti' : 'Existing attachments') ?>: <?= esc((string) count($attachments)) ?></small>
                <?php endif; ?>
            </label>
        </div>

        <label class="checkbox-row">
            <input type="checkbox" name="is_pinned" value="1" <?= old('is_pinned', (string) ($post['is_pinned'] ?? '0')) ? 'checked' : '' ?>>
            <span><?= esc(ui_locale() === 'it' ? 'Mantieni questo post in alto nella bacheca' : 'Keep this post pinned at the top of the board') ?></span>
        </label>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc($isEdit ? (ui_locale() === 'it' ? 'Salva post' : 'Save post') : (ui_locale() === 'it' ? 'Pubblica post' : 'Publish post')) ?></button>
            <a class="button button--secondary" href="<?= route_url('pinboard.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Annulla' : 'Cancel') ?></a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
