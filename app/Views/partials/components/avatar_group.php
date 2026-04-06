<?php $items = $items ?? []; ?>
<div class="avatar-group" aria-label="Participants">
    <?php foreach ($items as $item): ?>
        <?= view('partials/components/avatar', ['name' => (string) ($item['name'] ?? 'User'), 'src' => $item['src'] ?? null, 'size' => 'sm']) ?>
    <?php endforeach; ?>
</div>
