<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Membership detail</p>
        <h1><?= esc((string) ($membershipDetailContext['detail']['display_name'] ?? $membershipDetailContext['detail']['email'])) ?></h1>
        <p class="hero__lead">Details minimo della membership dentro il tenant corrente.</p>
    </div>
</section>

<section class="panel">
    <div class="detail-list">
        <div><span>Email</span><strong><?= esc((string) $membershipDetailContext['detail']['email']) ?></strong></div>
        <div><span>Status</span><strong><?= esc((string) $membershipDetailContext['detail']['status']) ?></strong></div>
        <div><span>Nickname</span><strong><?= esc((string) ($membershipDetailContext['detail']['nickname'] ?? '')) ?></strong></div>
        <div><span>Joined at</span><strong><?= esc((string) ($membershipDetailContext['detail']['joined_at'] ?? '')) ?></strong></div>
        <div><span>Roles</span><strong><?= esc((string) ($membershipDetailContext['detail']['role_names'] ?? '')) ?></strong></div>
    </div>
    <div class="hero__actions">
        <?php if ($canManageRoles && has_permission('manage_roles', $activeHousehold, $currentUserId)): ?>
            <a class="button button--primary" href="<?= route_url('membership.roles.edit', $membershipDetailContext['membership']['household_slug'], $membershipDetailContext['detail']['id']) ?>">Assegna ruoli</a>
        <?php endif; ?>
        <a class="button button--secondary" href="<?= route_url('memberships.index', $membershipDetailContext['membership']['household_slug']) ?>">Torna ai membri</a>
    </div>
</section>
<?= $this->endSection() ?>
