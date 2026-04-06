<?php helper('ui'); ?>
<?php $title = $title ?? ui_text('common.details'); ?>
<aside class="slide-over" data-slide-over hidden>
    <div class="slide-over__panel panel" role="dialog" aria-modal="true" aria-label="<?= esc((string) $title) ?>">
        <div class="section-heading">
            <div>
                <h2><?= esc((string) $title) ?></h2>
            </div>
            <button class="icon-button" type="button" data-slide-over-close><?= esc(ui_text('common.close')) ?></button>
        </div>
        <?= $content ?? '' ?>
    </div>
</aside>
