<?php $tabs = $tabs ?? []; ?>
<?php if ($tabs !== []): ?>
    <nav class="tabs" aria-label="Section tabs">
        <?php foreach ($tabs as $tab): ?>
            <a class="tabs__item <?= esc(! empty($tab['active']) ? 'is-active' : '') ?>" href="<?= esc((string) ($tab['href'] ?? '#')) ?>">
                <?= esc((string) ($tab['label'] ?? 'Tab')) ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
