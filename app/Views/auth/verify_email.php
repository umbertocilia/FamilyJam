<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="auth-card panel">
    <div class="auth-card__intro">
        <p class="eyebrow">Auth</p>
        <h1>Verify email</h1>
        <p><?= esc($authSubtitle ?? '') ?></p>
    </div>

    <div class="stack card border-0 shadow-sm">
        <div class="card-body">
        <?php if (! empty($isVerified)): ?>
            <p>Il tuo indirizzo email risulta gia verificato.</p>
            <a class="btn btn-primary" href="<?= route_url('households.index') ?>">Vai alle households</a>
        <?php else: ?>
            <p>Abbiamo generato un link di verifica. Se non lo trovi, puoi reinviare una nuova mail dal pulsante qui sotto.</p>
            <form method="post" action="<?= route_url('email.verify.resend') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary" type="submit">Reinvia verifica</button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
