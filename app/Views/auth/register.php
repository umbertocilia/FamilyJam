<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i><?= esc(ui_locale() === 'it' ? 'Create account' : 'Create account') ?></h3>
            </div>
            <form action="<?= route_url('auth.register.submit') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="invite_token" value="<?= esc((string) ($inviteToken ?? '')) ?>">

                <div class="card-body">
                    <?php if (! empty($invitationPreview['invitation'])): ?>
                        <div class="alert alert-info">
                            <?= esc(ui_locale() === 'it' ? 'Invito attivo per' : 'Active invitation for') ?>
                            <?= esc((string) $invitationPreview['invitation']['household_name']) ?>
                            (<?= esc((string) ($invitationPreview['invitation']['role_name'] ?? 'Member')) ?>)
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="displayName"><?= esc(ui_locale() === 'it' ? 'Display name' : 'Display name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'display_name')) ?>" id="displayName" type="text" name="display_name" value="<?= esc(old('display_name', (string) (($invitationPreview['existingUser']['display_name'] ?? '') ?: ''))) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email"><?= esc(ui_locale() === 'it' ? 'Email' : 'Email') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'email')) ?>" id="email" type="email" name="email" value="<?= esc(old('email', (string) ($invitationPreview['invitation']['email'] ?? ''))) ?>" autocomplete="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName"><?= esc(ui_locale() === 'it' ? 'First name' : 'First name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'first_name')) ?>" id="firstName" type="text" name="first_name" value="<?= esc(old('first_name')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName"><?= esc(ui_locale() === 'it' ? 'Last name' : 'Last name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'last_name')) ?>" id="lastName" type="text" name="last_name" value="<?= esc(old('last_name')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password"><?= esc(ui_locale() === 'it' ? 'Password' : 'Password') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'password')) ?>" id="password" type="password" name="password" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="passwordConfirmation"><?= esc(ui_locale() === 'it' ? 'Confirm password' : 'Confirm password') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'password_confirmation')) ?>" id="passwordConfirmation" type="password" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="locale"><?= esc(ui_text('profile.locale')) ?></label>
                                <select id="locale" name="locale" class="form-control">
                                    <option value="it" <?= old('locale', 'it') === 'it' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.it')) ?></option>
                                    <option value="en" <?= old('locale') === 'en' ? 'selected' : '' ?>><?= esc(ui_text('profile.locale.en')) ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="theme"><?= esc(ui_text('profile.theme')) ?></label>
                                <select id="theme" name="theme" class="form-control">
                                    <option value="system" <?= old('theme', 'system') === 'system' ? 'selected' : '' ?>><?= esc(ui_text('theme.system')) ?></option>
                                    <option value="light" <?= old('theme') === 'light' ? 'selected' : '' ?>><?= esc(ui_text('theme.light')) ?></option>
                                    <option value="dark" <?= old('theme') === 'dark' ? 'selected' : '' ?>><?= esc(ui_text('theme.dark')) ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="timezone"><?= esc(ui_text('profile.timezone')) ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'timezone')) ?>" id="timezone" type="text" name="timezone" value="<?= esc(old('timezone', 'Europe/Rome')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="icheck-primary">
                        <input type="checkbox" id="email_notifications" name="email_notifications" value="1" <?= old_bool('email_notifications', true) ? 'checked' : '' ?>>
                        <label for="email_notifications"><?= esc(ui_text('profile.email_notifications')) ?></label>
                    </div>
                </div>

                <div class="card-footer">
                    <button class="btn btn-primary" type="submit"><?= esc(ui_locale() === 'it' ? 'Create account' : 'Create account') ?></button>
                    <a class="btn btn-default ml-2" href="<?= route_url('auth.login') ?>"><?= esc(ui_locale() === 'it' ? 'I already have an account' : 'I already have an account') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
