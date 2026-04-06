<!doctype html>
<html lang="<?= esc(service('request')->getLocale() ?: 'it') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'FamilyJam') ?></title>
    <meta name="description" content="FamilyJam: multi-tenant household workspace for expenses, chores, shopping and communication.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte-v3/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte-v3/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte-v3/dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<?php $preferredTheme = is_array($currentUser ?? null) ? (string) ($currentUser['theme'] ?? 'system') : (string) (session('app.theme') ?? 'system'); ?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed <?= esc($pageClass ?? '') ?>" data-theme-preference="<?= esc($preferredTheme) ?>">
    <div class="wrapper">
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="<?= base_url('assets/images/FamilyJamLogo.png') ?>" alt="FamilyJam" height="72" width="72">
        </div>
        <?= $this->include('partials/navigation/topbar') ?>
        <?= $this->include('partials/navigation/sidebar') ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <?= $this->include('partials/alerts/messages') ?>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?= $this->renderSection('content') ?>
                </div>
            </section>
        </div>

        <?= $this->include('partials/footer') ?>
        <aside class="control-sidebar control-sidebar-dark"></aside>
    </div>

    <script src="<?= base_url('assets/adminlte-v3/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/adminlte-v3/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/adminlte-v3/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
    <script src="<?= base_url('assets/adminlte-v3/plugins/chart.js/Chart.min.js') ?>"></script>
    <script src="<?= base_url('assets/adminlte-v3/dist/js/adminlte.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>" defer></script>
</body>
</html>
