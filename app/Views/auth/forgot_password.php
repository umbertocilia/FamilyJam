<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-md-8 col-lg-5">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?= route_url('home') ?>" class="h1"><b>Family</b>Jam</a>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= esc(ui_locale() === 'it' ? 'Richiedi un link per reimpostare la password.' : 'Request a link to reset your password.') ?></p>
                <form action="<?= route_url('auth.forgot.submit') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="input-group mb-3">
                        <input class="form-control <?= esc(field_error_class($formErrors, 'email')) ?>" id="forgotEmail" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" placeholder="Email">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                        </div>
                    </div>
                    <?php if (field_error($formErrors, 'email') !== null): ?>
                        <small class="text-danger d-block mb-3"><?= esc((string) field_error($formErrors, 'email')) ?></small>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-block" type="submit"><?= esc(ui_locale() === 'it' ? 'Invia reset link' : 'Send reset link') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
