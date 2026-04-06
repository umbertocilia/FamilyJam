<?php helper('ui'); ?>
<?php $title = $title ?? (ui_locale() === 'it' ? 'Finestra' : 'Modal'); ?>
<div class="modal" data-modal hidden>
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog panel" role="dialog" aria-modal="true" aria-label="<?= esc((string) $title) ?>">
        <div class="section-heading">
            <div>
                <h2><?= esc((string) $title) ?></h2>
            </div>
            <button class="icon-button" type="button" data-modal-close><?= esc(ui_text('common.close')) ?></button>
        </div>
        <?= $content ?? '' ?>
    </div>
</div>
