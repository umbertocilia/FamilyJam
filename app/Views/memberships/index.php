<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Memberships</p>
        <h1><?= esc($membershipContext['membership']['household_name']) ?> members</h1>
        <p class="hero__lead">Active memberships, access status, roles and pending invitations for the current household.</p>
        <div class="quick-filter-bar" aria-label="Member shortcuts">
            <a class="module-chip" href="<?= route_url('memberships.index', $membershipContext['membership']['household_slug']) ?>">Members</a>
            <?php if ($canManageRoles): ?>
                <a class="module-chip" href="<?= route_url('roles.index', $membershipContext['membership']['household_slug']) ?>">Roles</a>
            <?php endif; ?>
            <?php if ($canManageMembers): ?>
                <a class="module-chip" href="<?= route_url('invitations.index', $membershipContext['membership']['household_slug']) ?>">Invitations</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canManageRoles): ?>
        <div class="hero__actions">
            <a class="button button--secondary" href="<?= route_url('roles.index', $membershipContext['membership']['household_slug']) ?>">Manage roles</a>
        </div>
    <?php endif; ?>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Members</p>
                <h2>Member list</h2>
            </div>
        </div>

        <div class="list-table">
            <?php if ($membershipContext['members'] === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun membro ancora' : 'No members yet'; $message = ui_locale() === 'it' ? 'Le membership attive appariranno qui non appena la household verra popolata.' : 'Active memberships will appear here as soon as the household is populated.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-users'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($membershipContext['members'] as $member): ?>
                    <a class="list-table__row" href="<?= route_url('memberships.show', $membershipContext['membership']['household_slug'], $member['id']) ?>">
                        <div>
                            <strong><?= esc((string) ($member['display_name'] ?? $member['email'])) ?></strong>
                            <p><?= esc((string) $member['email']) ?></p>
                        </div>
                        <div class="list-table__meta">
                            <span><?= esc((string) $member['status']) ?></span>
                            <small><?= esc((string) ($member['role_codes'] ?? '')) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Invitations</p>
                <h2>Invitations and onboarding</h2>
            </div>
        </div>

        <?php if ($canManageMembers): ?>
            <form class="auth-form auth-form--compact" action="<?= route_url('invitations.create', $membershipContext['membership']['household_slug']) ?>" method="post">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= esc(old('email')) ?>">
                </label>
                <label class="field">
                    <span>Role</span>
                    <select name="role_code">
                        <?php foreach ($assignableRoles as $role): ?>
                            <option value="<?= esc((string) $role['code']) ?>" <?= old('role_code', 'member') === $role['code'] ? 'selected' : '' ?>><?= esc((string) $role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Message</span>
                    <textarea name="message" rows="3"><?= esc(old('message')) ?></textarea>
                </label>
                <button class="button button--primary" type="submit">Create invitation</button>
            </form>
        <?php else: ?>
            <p>You do not have permission to create or revoke invitations.</p>
        <?php endif; ?>

        <div class="stack-list">
            <?php if ($pendingInvitations === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun invito pendente' : 'No pending invitations'; $message = ui_locale() === 'it' ? 'La coda inviti e attualmente vuota.' : 'The invitation queue is currently empty.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-envelope-open'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($pendingInvitations as $invitation): ?>
                    <div class="row-card">
                        <div>
                            <strong><?= esc((string) $invitation['email']) ?></strong>
                            <p><?= esc((string) ($invitation['role_name'] ?? 'Member')) ?> - expires <?= esc((string) $invitation['expires_at']) ?></p>
                        </div>
                        <?php if ($canManageMembers): ?>
                            <form method="post" action="<?= route_url('invitations.revoke', $membershipContext['membership']['household_slug'], $invitation['id']) ?>">
                                <?= csrf_field() ?>
                                <button class="button button--secondary" type="submit">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>
<?= $this->endSection() ?>
