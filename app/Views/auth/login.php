<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-md-8 col-lg-5">
        <div class="login-box w-100">
            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <a href="<?= route_url('home') ?>" class="h1"><b>Family</b>Jam</a>
                </div>
                <div class="card-body">
                    <p class="login-box-msg"><?= esc($authSubtitle ?? (ui_locale() === 'it' ? 'Accedi al tuo workspace household.' : 'Sign in to your household workspace.')) ?></p>
                    <?php if (! empty($inviteToken)): ?>
                        <div class="alert alert-info">
                            <?= esc(ui_locale() === 'it'
                                ? 'Hai un invito in attesa. Dopo il login proveremo a collegarlo al tuo account.'
                                : 'You have a pending invitation. After login we will try to attach it to your account.') ?>
                        </div>
                    <?php endif; ?>
                    <?= $this->include('auth/_login_form') ?>
                    <p class="mb-0 mt-3 text-center">
                        <a href="<?= route_url('auth.register') ?>" class="text-center"><?= esc(ui_locale() === 'it' ? 'Crea un nuovo account' : 'Create a new account') ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
