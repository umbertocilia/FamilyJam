<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$avatarPath = old('avatar_path', (string) ($profile['avatar_path'] ?? ''));
$avatarUrl = $avatarPath !== ''
    ? base_url(ltrim($avatarPath, '/'))
    : base_url('assets/adminlte-v3/dist/img/user2-160x160.jpg');
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h1><?= esc(ui_text('profile.title')) ?></h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= route_url('households.index') ?>"><?= esc(ui_locale() === 'it' ? 'Home' : 'Home') ?></a></li>
            <li class="breadcrumb-item active"><?= esc(ui_text('profile.title')) ?></li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-4 col-xl-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle profile-avatar-preview" src="<?= esc($avatarUrl) ?>" alt="<?= esc((string) ($profile['display_name'] ?? 'Profile')) ?>">
                </div>

                <h3 class="profile-username text-center"><?= esc((string) ($profile['display_name'] ?? '')) ?></h3>
                <p class="text-muted text-center"><?= esc((string) ($currentUser['email'] ?? '')) ?></p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b><?= esc(ui_text('profile.locale')) ?></b> <span class="float-right"><?= esc((string) strtoupper((string) ($profile['locale'] ?? 'en'))) ?></span>
                    </li>
                    <li class="list-group-item">
                        <b><?= esc(ui_text('profile.theme')) ?></b> <span class="float-right"><?= esc(ui_text('theme.' . (string) ($profile['theme'] ?? 'system'))) ?></span>
                    </li>
                    <li class="list-group-item">
                        <b><?= esc(ui_text('profile.timezone')) ?></b> <span class="float-right"><?= esc((string) ($profile['timezone'] ?? 'Europe/Rome')) ?></span>
                    </li>
                </ul>

                <?php if (! empty($currentUser['email_verified_at'])): ?>
                    <span class="badge badge-success"><?= esc(ui_locale() === 'it' ? 'Email verificata' : 'Email verified') ?></span>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-block" href="<?= route_url('email.verify.notice') ?>"><?= esc(ui_text('profile.verify_email')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8 col-xl-9">
        <form class="profile-form" action="<?= route_url('profile.update') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><?= esc(ui_text('profile.identity')) ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?= esc(ui_text('profile.identity.help')) ?></p>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="displayName"><?= esc(ui_locale() === 'it' ? 'Nome visualizzato' : 'Display name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'display_name')) ?>" id="displayName" type="text" name="display_name" value="<?= esc(old('display_name', (string) ($profile['display_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName"><?= esc(ui_locale() === 'it' ? 'Nome' : 'First name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'first_name')) ?>" id="firstName" type="text" name="first_name" value="<?= esc(old('first_name', (string) ($profile['first_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName"><?= esc(ui_locale() === 'it' ? 'Cognome' : 'Last name') ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'last_name')) ?>" id="lastName" type="text" name="last_name" value="<?= esc(old('last_name', (string) ($profile['last_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="avatarImage"><?= esc(ui_locale() === 'it' ? 'Immagine avatar' : 'Avatar image') ?></label>
                                <div class="custom-file">
                                    <input class="custom-file-input <?= esc(field_error_class($formErrors, 'avatar_path')) ?>" id="avatarImage" type="file" name="avatar_image" accept=".jpg,.jpeg,.png,.webp">
                                    <label class="custom-file-label" for="avatarImage"><?= esc(ui_locale() === 'it' ? 'Scegli file' : 'Choose file') ?></label>
                                </div>
                                <small class="form-text text-muted"><?= esc(ui_locale() === 'it' ? 'Le immagini vengono ridimensionate automaticamente per il web.' : 'Images are resized automatically for the web.') ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><?= esc(ui_text('profile.preferences')) ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?= esc(ui_text('profile.preferences.help')) ?></p>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="locale"><?= esc(ui_text('profile.locale')) ?></label>
                                <select id="locale" name="locale" class="form-control">
                                    <?php $selectedLocale = old('locale', (string) ($profile['locale'] ?? 'en')); ?>
                                    <?php foreach (ui_supported_locales() as $locale): ?>
                                        <option value="<?= esc($locale) ?>" <?= $selectedLocale === $locale ? 'selected' : '' ?>>
                                            <?= esc($locale === 'it' ? ui_text('profile.locale.it') : ui_text('profile.locale.en')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted"><?= esc(ui_text('profile.locale.help')) ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="theme"><?= esc(ui_text('profile.theme')) ?></label>
                                <select id="theme" name="theme" class="form-control">
                                    <?php foreach (['system', 'light', 'dark'] as $value): ?>
                                        <option value="<?= esc($value) ?>" <?= old('theme', (string) ($profile['theme'] ?? 'system')) === $value ? 'selected' : '' ?>>
                                            <?= esc(ui_text('theme.' . $value)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="timezone"><?= esc(ui_text('profile.timezone')) ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'timezone')) ?>" id="timezone" type="text" name="timezone" value="<?= esc(old('timezone', (string) ($profile['timezone'] ?? 'Europe/Rome'))) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="icheck-primary mt-2">
                        <input type="checkbox" id="emailNotifications" name="email_notifications" value="1" <?= old_bool('email_notifications', (bool) ($profile['email_notifications'] ?? true)) ? 'checked' : '' ?>>
                        <label for="emailNotifications"><?= esc(ui_text('profile.email_notifications')) ?></label>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary" type="submit"><?= esc(ui_text('profile.save')) ?></button>
                </div>
            </div>
        </form>

        <form action="<?= route_url('profile.delete') ?>" method="post" class="mt-4">
            <?= csrf_field() ?>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><?= esc(ui_text('profile.delete.title')) ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3"><?= esc(ui_text('profile.delete.lead')) ?></p>
                    <div class="alert alert-warning mb-4">
                        <?= esc(ui_text('profile.delete.help')) ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currentPassword"><?= esc(ui_text('profile.delete.password')) ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'current_password')) ?>" id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmationPhrase"><?= esc(ui_text('profile.delete.confirm')) ?></label>
                                <input class="form-control <?= esc(field_error_class($formErrors, 'confirmation_phrase')) ?>" id="confirmationPhrase" type="text" name="confirmation_phrase" value="<?= esc(old('confirmation_phrase')) ?>" maxlength="32" required>
                                <small class="form-text text-muted"><?= esc(ui_text('profile.delete.confirm.help')) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-danger" type="submit"><?= esc(ui_text('profile.delete.submit')) ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
