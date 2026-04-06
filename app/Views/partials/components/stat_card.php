<?php
$label = $label ?? '';
$value = $value ?? '';
$hint = $hint ?? '';
$tone = $tone ?? 'default';
$icon = $icon ?? match ($tone) {
    'success' => 'fas fa-check-circle',
    'warning' => 'fas fa-exclamation-triangle',
    'danger' => 'fas fa-bolt',
    'info' => 'fas fa-chart-line',
    default => 'fas fa-chart-pie',
};

$bgClass = match ($tone) {
    'success' => 'bg-success',
    'warning' => 'bg-warning',
    'danger' => 'bg-danger',
    'info' => 'bg-info',
    default => 'bg-primary',
};
?>
<div class="info-box">
    <span class="info-box-icon <?= esc($bgClass) ?>"><i class="<?= esc((string) $icon) ?>"></i></span>
    <div class="info-box-content">
        <span class="info-box-text"><?= esc((string) $label) ?></span>
        <span class="info-box-number"><?= esc((string) $value) ?></span>
        <?php if ((string) $hint !== ''): ?>
            <span class="text-muted text-sm"><?= esc((string) $hint) ?></span>
        <?php endif; ?>
    </div>
</div>
