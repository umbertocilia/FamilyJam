<!doctype html>
<html lang="<?= esc(service('request')->getLocale() ?: 'it') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'FamilyJam') ?></title>
    <meta name="description" content="FamilyJam public access and account flows.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte-v3/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte-v3/dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<?php $preferredTheme = is_array($currentUser ?? null) ? (string) ($currentUser['theme'] ?? 'system') : (string) (session('app.theme') ?? 'system'); ?>
<body class="hold-transition layout-top-nav <?= esc($pageClass ?? '') ?>" data-theme-preference="<?= esc($preferredTheme) ?>" data-consent-preferences="<?= ! empty($privacyConsent['preferencesPersistAllowed']) ? 'true' : 'false' ?>" data-user-authenticated="<?= $currentUserId === null ? 'false' : 'true' ?>">
<div class="wrapper">
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="<?= base_url('assets/images/FamilyJamLogo.png') ?>" alt="FamilyJam" height="72" width="72">
    </div>

    <?= $this->include('partials/navigation/header') ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container">
                <?= $this->include('partials/alerts/messages') ?>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <?= $this->include('partials/footer') ?>
    <?= $this->include('partials/legal/cookie_consent') ?>
</div>

<script src="<?= base_url('assets/adminlte-v3/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte-v3/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte-v3/dist/js/adminlte.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>" defer></script>
</body>
</html>
