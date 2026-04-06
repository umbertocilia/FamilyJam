<?php
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $notification */
/** @var array<string, mixed>|null $household */
$householdName = $household['name'] ?? $notification['household_name'] ?? null;
$targetUrl = $notification['target_url'] ?? null;
?>
<html lang="it">
<body style="margin:0;padding:24px;background:#f4f7fb;color:#102031;font-family:Segoe UI,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:620px;background:#ffffff;border-radius:20px;border:1px solid #d9e4ef;padding:32px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 12px;color:#5c6b7d;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">FamilyJam Notification</p>
                            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.1;"><?= esc((string) ($notification['title'] ?? 'Nuova notifica')) ?></h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Ciao <?= esc((string) ($user['display_name'] ?? '')) ?>,</p>
                            <?php if (! empty($notification['body'])): ?>
                                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;"><?= esc((string) $notification['body']) ?></p>
                            <?php endif; ?>
                            <?php if ($householdName !== null): ?>
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#5c6b7d;">Household: <?= esc((string) $householdName) ?></p>
                            <?php endif; ?>
                            <?php if (is_string($targetUrl) && $targetUrl !== ''): ?>
                                <p style="margin:24px 0 0;">
                                    <a href="<?= esc($targetUrl) ?>" style="display:inline-block;padding:14px 22px;border-radius:999px;background:#1272d0;color:#ffffff;text-decoration:none;font-weight:700;">Open notifica</a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
