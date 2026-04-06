<nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
        <a href="<?= route_url('home') ?>" class="navbar-brand">
            <img src="<?= base_url('assets/images/FamilyJamLogo.png') ?>" alt="FamilyJam" class="brand-image img-circle elevation-2">
            <span class="brand-text font-weight-light"><?= esc($appShell['brand']['name']) ?></span>
        </a>

        <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#public-navbar" aria-controls="public-navbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-3" id="public-navbar">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a href="<?= route_url('home') ?>" class="nav-link"><?= esc(ui_locale() === 'it' ? 'Home' : 'Home') ?></a>
                </li>
                <li class="nav-item">
                    <a href="<?= route_url('home') ?>#info-app" class="nav-link"><?= esc(ui_locale() === 'it' ? 'Informazioni' : 'About') ?></a>
                </li>
                <li class="nav-item">
                    <button class="btn btn-default btn-sm ml-md-2" type="button" data-theme-toggle>
                        <i class="fas fa-adjust mr-1"></i><span data-theme-toggle-label><?= esc(ui_text('theme')) ?>: <?= esc(ui_text('theme.system')) ?></span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>
