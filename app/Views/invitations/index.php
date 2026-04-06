<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Invitations</p>
        <h1><?= esc($membershipContext['membership']['household_name']) ?> invitations</h1>
        <p class="hero__lead">Create, review and revoke pending invitations for the active household.</p>
    </div>

    <div class="hero__actions">
        <a class="button button--secondary" href="<?= route_url('memberships.index', $membershipContext['membership']['household_slug']) ?>">Back to members</a>
    </div>
</section>

<section class="content-grid">
    <article class="panel panel--accent">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Create</p>
                <h2>New invitation</h2>
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
                <button class="button button--primary" type="submit">Send invitation</button>
            </form>
        <?php else: ?>
            <p>Your membership cannot create or revoke invitations.</p>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <p class="section-heading__eyebrow">Pending</p>
                <h2>Pending invitations</h2>
            </div>
        </div>

        <div class="stack-list">
            <?php if ($pendingInvitations === []): ?>
                <?php $title = ui_locale() === 'it' ? 'Nessun invito pendente' : 'No pending invitations'; $message = ui_locale() === 'it' ? 'La coda e vuota in questo momento.' : 'The queue is empty right now.'; $actionLabel = null; $actionHref = null; $icon = 'fas fa-envelope-open'; ?>
                <?= $this->include('partials/components/empty_state') ?>
            <?php else: ?>
                <?php foreach ($pendingInvitations as $invitation): ?>
                    <div class="row-card">
                        <div>
                            <strong><?= esc((string) $invitation['email']) ?></strong>
                            <p><?= esc((string) ($invitation['role_name'] ?? 'Member')) ?> - expires <?= esc((string) $invitation['expires_at']) ?></p>
                            <?php if (! empty($invitation['invited_by_name'])): ?>
                                <small>Created by <?= esc((string) $invitation['invited_by_name']) ?></small>
                            <?php endif; ?>
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
