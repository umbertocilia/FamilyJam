<?php
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $notification */
/** @var array<string, mixed>|null $household */
$householdName = $household['name'] ?? $notification['household_name'] ?? null;
$targetUrl = $notification['target_url'] ?? null;
?>
Ciao <?= (string) ($user['display_name'] ?? '') ?>,

<?= (string) ($notification['title'] ?? 'Nuova notifica') ?>

<?php if (! empty($notification['body'])): ?>
<?= (string) $notification['body'] . "\n\n" ?>
<?php endif; ?>
<?php if ($householdName !== null): ?>
Household: <?= (string) $householdName . "\n\n" ?>
<?php endif; ?>
<?php if (is_string($targetUrl) && $targetUrl !== ''): ?>
Open qui:
<?= $targetUrl . "\n" ?>
<?php endif; ?>
