<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= $currentUserId === null ? route_url('home') : route_url('households.index') ?>" class="brand-link">
        <img src="<?= base_url('assets/images/FamilyJamLogo.png') ?>" alt="FamilyJam" class="brand-image img-circle elevation-3">
        <span class="brand-text font-weight-light"><?= esc($appShell['brand']['name']) ?></span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= esc((string) (($currentUser['avatar_path'] ?? '') !== '' ? base_url(ltrim((string) $currentUser['avatar_path'], '/')) : base_url('assets/adminlte-v3/dist/img/user2-160x160.jpg'))) ?>" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="<?= $currentUserId === null ? route_url('auth.login') : route_url('profile.edit') ?>" class="d-block">
                    <?= esc($currentUserId === null ? ui_text('shell.guest') : (string) ($currentUser['display_name'] ?? ui_text('shell.profile'))) ?>
                </a>
                <?php if ($activeHousehold !== null): ?>
                    <span class="d-block text-sm text-muted"><?= esc((string) $activeHousehold['household_name']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="<?= esc(ui_locale() === 'it' ? 'Cerca' : 'Search') ?>" aria-label="<?= esc(ui_locale() === 'it' ? 'Cerca' : 'Search') ?>">
                <div class="input-group-append">
                    <button class="btn btn-sidebar" type="button">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <?php foreach ($appShell['sidebarNavigation'] as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (bool) $item['isActive'] ? 'active' : '' ?>" href="<?= esc((string) $item['href']) ?>">
                            <i class="<?= esc((string) ($item['icon'] ?? 'nav-icon far fa-circle')) ?>"></i>
                            <p><?= esc((string) $item['label']) ?></p>
                        </a>
                    </li>
                <?php endforeach; ?>

                <?php if (($appShell['utilityNavigation'] ?? []) !== []): ?>
                    <li class="nav-header"><?= esc(ui_text('shell.workspace_tools')) ?></li>
                    <?php foreach ($appShell['utilityNavigation'] as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (bool) $item['isActive'] ? 'active' : '' ?>" href="<?= esc((string) $item['href']) ?>">
                                <i class="<?= esc((string) ($item['icon'] ?? 'nav-icon far fa-circle')) ?>"></i>
                                <p><?= esc((string) $item['label']) ?></p>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</aside>
