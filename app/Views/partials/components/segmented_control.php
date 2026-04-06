<?php $items = $items ?? []; ?>
<?php if ($items !== []): ?>
    <div class="segmented-control" role="tablist">
        <?php foreach ($items as $item): ?>
            <a class="segmented-control__item <?= esc(! empty($item['active']) ? 'is-active' : '') ?>" href="<?= esc((string) ($item['href'] ?? '#')) ?>">
                <?= esc((string) ($item['label'] ?? 'Option')) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
