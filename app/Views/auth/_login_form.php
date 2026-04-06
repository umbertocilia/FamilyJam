<?php
$inviteToken = is_string($inviteToken ?? null) ? (string) $inviteToken : '';
?>
<form action="<?= route_url('auth.login.submit') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="invite_token" value="<?= esc($inviteToken) ?>">

    <div class="input-group mb-3">
        <input class="form-control <?= esc(field_error_class($formErrors, 'email')) ?>" id="loginEmail" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" placeholder="<?= esc(ui_locale() === 'it' ? 'Email' : 'Email') ?>">
        <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
        </div>
    </div>
    <?php if (field_error($formErrors, 'email') !== null): ?>
        <small class="text-danger d-block mb-3"><?= esc((string) field_error($formErrors, 'email')) ?></small>
    <?php endif; ?>

    <div class="input-group mb-3">
        <input class="form-control <?= esc(field_error_class($formErrors, 'password')) ?>" id="loginPassword" type="password" name="password" autocomplete="current-password" placeholder="<?= esc(ui_locale() === 'it' ? 'Password' : 'Password') ?>">
        <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
        </div>
    </div>
    <?php if (field_error($formErrors, 'password') !== null): ?>
        <small class="text-danger d-block mb-3"><?= esc((string) field_error($formErrors, 'password')) ?></small>
    <?php endif; ?>

    <div class="row">
        <div class="col-7">
            <div class="icheck-primary">
                <input type="checkbox" id="remember">
                <label for="remember"><?= esc(ui_locale() === 'it' ? 'Ricordami' : 'Remember me') ?></label>
            </div>
        </div>
        <div class="col-5">
            <button type="submit" class="btn btn-primary btn-block"><?= esc(ui_text('shell.log_in')) ?></button>
        </div>
    </div>

    <p class="mb-1 mt-3">
        <a href="<?= route_url('auth.forgot') ?>"><?= esc(ui_locale() === 'it' ? 'Password dimenticata?' : 'I forgot my password') ?></a>
    </p>
</form>
