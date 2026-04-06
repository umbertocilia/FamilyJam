<?php
$title = $title ?? 'Nothing here yet';
$message = $message ?? 'This area will populate as soon as data becomes available.';
$actionLabel = $actionLabel ?? null;
$actionHref = $actionHref ?? null;
$icon = $icon ?? 'fas fa-inbox';
?>
<div class="card card-outline card-primary empty-state">
    <div class="card-body">
        <div class="empty-state__art" aria-hidden="true">
            <i class="<?= esc((string) $icon) ?> text-primary"></i>
        </div>
        <h5 class="mb-1"><?= esc((string) $title) ?></h5>
        <p class="text-muted mb-0"><?= esc((string) $message) ?></p>
        <?php if (is_string($actionLabel) && is_string($actionHref)): ?>
            <a class="btn btn-primary" href="<?= esc($actionHref) ?>"><?= esc($actionLabel) ?></a>
        <?php endif; ?>
    </div>
</div>
