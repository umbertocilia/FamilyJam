<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-md-8 col-lg-5">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?= route_url('home') ?>" class="h1"><b>Family</b>Jam</a>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= esc(ui_locale() === 'it' ? 'Imposta una nuova password per il tuo account.' : 'Set a new password for your account.') ?></p>
                <?php if (empty($resetPreview)): ?>
                    <div class="alert alert-warning">
                        <?= esc(ui_locale() === 'it' ? 'Questo token non e piu valido. Richiedi un nuovo reset password.' : 'This token is no longer valid. Request a new password reset.') ?>
                    </div>
                    <a class="btn btn-primary btn-block" href="<?= route_url('auth.forgot') ?>"><?= esc(ui_locale() === 'it' ? 'Nuovo reset' : 'New reset') ?></a>
                <?php else: ?>
                    <form action="<?= route_url('auth.reset.submit', $resetToken) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="input-group mb-3">
                            <input class="form-control <?= esc(field_error_class($formErrors, 'password')) ?>" id="resetPassword" type="password" name="password" autocomplete="new-password" placeholder="<?= esc(ui_locale() === 'it' ? 'Nuova password' : 'New password') ?>">
                            <div class="input-group-append">
                                <div class="input-group-text"><span class="fas fa-lock"></span></div>
                            </div>
                        </div>
                        <?php if (field_error($formErrors, 'password') !== null): ?>
                            <small class="text-danger d-block mb-3"><?= esc((string) field_error($formErrors, 'password')) ?></small>
                        <?php endif; ?>

                        <div class="input-group mb-3">
                            <input class="form-control <?= esc(field_error_class($formErrors, 'password_confirmation')) ?>" id="resetPasswordConfirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="<?= esc(ui_locale() === 'it' ? 'Conferma password' : 'Confirm password') ?>">
                            <div class="input-group-append">
                                <div class="input-group-text"><span class="fas fa-check"></span></div>
                            </div>
                        </div>
                        <?php if (field_error($formErrors, 'password_confirmation') !== null): ?>
                            <small class="text-danger d-block mb-3"><?= esc((string) field_error($formErrors, 'password_confirmation')) ?></small>
                        <?php endif; ?>

                        <button class="btn btn-primary btn-block" type="submit"><?= esc(ui_locale() === 'it' ? 'Aggiorna password' : 'Update password') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
