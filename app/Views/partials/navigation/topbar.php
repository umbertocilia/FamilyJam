<?php $notificationUi = $appShell['uiNotifications']; ?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="<?= esc(ui_text('shell.menu')) ?>">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= $currentUserId === null ? route_url('home') : route_url('households.index') ?>" class="nav-link">
                <?= esc(ui_locale() === 'it' ? 'Home' : 'Home') ?>
            </a>
        </li>
        <?php if ($activeHousehold !== null): ?>
            <li class="nav-item d-none d-md-inline-block">
                <a href="<?= route_url('households.dashboard', (string) ($activeHousehold['household_slug'] ?? '')) ?>" class="nav-link">
                    <?= esc(active_household_name($activeHousehold, ui_text('shell.household'))) ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-label="<?= esc(ui_text('shell.active_household')) ?>">
                <i class="fas fa-home"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?= esc(ui_text('shell.active_household')) ?></span>
                <div class="dropdown-divider"></div>
                <?php if ($currentUserId === null): ?>
                    <span class="dropdown-item text-muted"><?= esc(ui_text('shell.login_to_switch')) ?></span>
                <?php elseif ($appShell['householdSwitcher']['available'] === []): ?>
                    <span class="dropdown-item text-muted"><?= esc(ui_text('shell.no_household_available')) ?></span>
                <?php else: ?>
                    <?php foreach ($appShell['householdSwitcher']['available'] as $household): ?>
                        <form method="post" action="<?= route_url('households.switch', $household['slug']) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button class="dropdown-item<?= $household['isCurrent'] ? ' active' : '' ?>" type="submit" <?= $household['isCurrent'] ? 'disabled' : '' ?>>
                                <i class="fas fa-users mr-2 text-info"></i>
                                <div class="d-inline-block align-middle">
                                    <span class="d-block"><?= esc($household['name']) ?></span>
                                    <small class="text-muted"><?= esc($household['roles']) ?></small>
                                </div>
                            </button>
                        </form>
                        <div class="dropdown-divider"></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= route_url('households.switcher') ?>" class="dropdown-item dropdown-footer"><?= esc(ui_locale() === 'it' ? 'Apri selettore' : 'Open switcher') ?></a>
            </div>
        </li>

        <?= $this->include('partials/ui/notifications') ?>

        <li class="nav-item">
            <a class="nav-link" href="#" data-theme-toggle aria-label="<?= esc(ui_text('shell.theme')) ?>">
                <i class="fas fa-adjust"></i>
                <span class="d-none d-xl-inline ml-1" data-theme-toggle-label></span>
            </a>
        </li>

        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <img src="<?= esc((string) (($currentUser['avatar_path'] ?? '') !== '' ? base_url(ltrim((string) $currentUser['avatar_path'], '/')) : base_url('assets/adminlte-v3/dist/img/user2-160x160.jpg'))) ?>" class="user-image img-circle elevation-2" alt="User Image">
                <span class="d-none d-md-inline"><?= esc($currentUserId === null ? ui_text('shell.guest') : (string) ($currentUser['display_name'] ?? ui_text('shell.profile'))) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <?php if ($currentUserId === null): ?>
                    <li class="user-header bg-primary">
                        <img src="<?= base_url('assets/adminlte-v3/dist/img/avatar5.png') ?>" class="img-circle elevation-2" alt="Guest">
                        <p><?= esc(ui_text('shell.guest')) ?></p>
                    </li>
                    <li class="user-footer">
                        <a href="<?= route_url('auth.login') ?>" class="btn btn-default btn-flat"><?= esc(ui_text('shell.log_in')) ?></a>
                        <a href="<?= route_url('auth.register') ?>" class="btn btn-default btn-flat float-right"><?= esc(ui_text('shell.create_account')) ?></a>
                    </li>
                <?php else: ?>
                    <li class="user-header bg-info">
                        <img src="<?= esc((string) (($currentUser['avatar_path'] ?? '') !== '' ? base_url(ltrim((string) $currentUser['avatar_path'], '/')) : base_url('assets/adminlte-v3/dist/img/user2-160x160.jpg'))) ?>" class="img-circle elevation-2" alt="User Image">
                        <p>
                            <?= esc((string) ($currentUser['display_name'] ?? ui_text('shell.profile'))) ?>
                            <small><?= esc((string) ($currentUser['email'] ?? '')) ?></small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="<?= route_url('profile.edit') ?>" class="btn btn-default btn-flat"><?= esc(ui_text('shell.profile')) ?></a>
                        <form method="post" action="<?= route_url('auth.logout') ?>" class="float-right">
                            <?= csrf_field() ?>
                            <button class="btn btn-default btn-flat" type="submit"><?= esc(ui_text('shell.logout')) ?></button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>
</nav>
