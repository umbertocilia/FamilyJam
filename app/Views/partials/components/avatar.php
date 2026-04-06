<?php
$name = (string) ($name ?? 'FamilyJam');
$size = (string) ($size ?? 'md');
$src = $src ?? null;
?>
<span class="avatar avatar--<?= esc($size) ?>">
    <?php if (is_string($src) && $src !== ''): ?>
        <img src="<?= esc($src) ?>" alt="<?= esc($name) ?>">
    <?php else: ?>
        <span><?= esc(ui_initials($name)) ?></span>
    <?php endif; ?>
</span>
