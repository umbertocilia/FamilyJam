<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="auth-card panel">
    <div class="auth-card__intro">
        <p class="eyebrow">Invitation</p>
        <h1>Accept invitation</h1>
        <?php if (empty($invitationPreview['invitation'])): ?>
            <p>L'invito non e valido, e scaduto oppure e gia stato usato.</p>
        <?php else: ?>
            <p>
                Sei stato invitato a <strong><?= esc($invitationPreview['invitation']['household_name']) ?></strong>
                come <strong><?= esc((string) ($invitationPreview['invitation']['role_name'] ?? 'Member')) ?></strong>.
            </p>
        <?php endif; ?>
    </div>

    <?php if (empty($invitationPreview['invitation'])): ?>
        <div class="hero__actions">
            <a class="button button--primary" href="<?= route_url('home') ?>">Torna alla home</a>
        </div>
    <?php elseif ($currentUserId === null): ?>
        <div class="stack">
            <p>Per accettare l'invito devi accedere oppure registrare un account con la stessa email invitata: <?= esc((string) $invitationPreview['invitation']['email']) ?>.</p>
            <div class="hero__actions">
                <a class="button button--primary" href="<?= route_url('auth.login') ?>?invite=<?= urlencode((string) $inviteToken) ?>">Login</a>
                <a class="button button--secondary" href="<?= route_url('auth.register') ?>?invite=<?= urlencode((string) $inviteToken) ?>">Register</a>
            </div>
        </div>
    <?php elseif (! $inviteEmailMatchesCurrentUser): ?>
        <div class="stack">
            <p>L'account autenticato non corrisponde all'email invitata.</p>
            <div class="hero__actions">
                <form method="post" action="<?= route_url('auth.logout') ?>">
                    <?= csrf_field() ?>
                    <button class="button button--primary" type="submit">Logout e cambia account</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <form method="post" action="<?= route_url('invitations.accept.submit', $inviteToken) ?>">
            <?= csrf_field() ?>
            <button class="button button--primary" type="submit">Accetta invito</button>
        </form>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
