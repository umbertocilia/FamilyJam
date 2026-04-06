<?php helper('ui'); ?>
<?php $messages = flash_messages(); ?>
<?php $errorList = $formErrors ?? []; ?>
<?php if ($messages !== [] || $errorList !== []): ?>
    <section class="alerts mb-3" aria-label="Flash messages" aria-live="polite">
        <?php foreach ($messages as $message): ?>
            <?php
            $alertClass = match ($message['type']) {
                'success' => 'alert-success',
                'warning' => 'alert-warning',
                'error' => 'alert-danger',
                default => 'alert-info',
            };
            ?>
            <article class="alert <?= esc($alertClass) ?> alert-dismissible fade show" data-alert role="alert">
                <strong><?= esc(ucfirst($message['type'])) ?></strong>
                <div><?= esc($message['message']) ?></div>
                <button class="close" type="button" data-alert-dismiss aria-label="<?= esc(ui_text('common.close')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </article>
        <?php endforeach; ?>
        <?php if ($errorList !== []): ?>
            <article class="alert alert-danger alert-dismissible fade show" data-alert role="alert">
                <strong><?= esc(ui_locale() === 'it' ? 'Validazione' : 'Validation') ?></strong>
                <div><?= esc(implode(' ', $errorList)) ?></div>
                <button class="close" type="button" data-alert-dismiss aria-label="<?= esc(ui_text('common.close')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </article>
        <?php endif; ?>
    </section>
<?php endif; ?>
