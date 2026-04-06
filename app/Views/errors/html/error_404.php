<!doctype html>
<?php helper('ui'); ?>
<html lang="<?= esc(service('request')->getLocale() ?: 'it') ?>" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= lang('Errors.pageNotFound') ?> | FamilyJam</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="error-page">
<div class="shell shell--auth">
    <header class="public-header">
        <a class="brandmark" href="<?= site_url('/') ?>">
            <span class="brandmark__pill">FJ</span>
            <span class="brandmark__text">
                <strong>FamilyJam</strong>
                <small><?= esc(ui_locale() === 'it' ? 'Sistema casa' : 'Household OS') ?></small>
            </span>
        </a>
        <nav class="public-nav" aria-label="Error actions">
            <a class="public-nav__link" href="<?= site_url('/') ?>">Home</a>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="<?= esc(ui_locale() === 'it' ? 'Cambia tema' : 'Change theme') ?>"><?= esc(ui_text('theme')) ?></button>
        </nav>
    </header>

    <main class="auth-main">
        <section class="panel panel--hero">
            <div class="stack">
                <p class="eyebrow">HTTP 404</p>
                <h1><?= lang('Errors.pageNotFound') ?></h1>
                <p class="hero__lead">
                    <?php if (ENVIRONMENT !== 'production') : ?>
                        <?= nl2br(esc($message)) ?>
                    <?php else : ?>
                        <?= lang('Errors.sorryCannotFind') ?>
                    <?php endif; ?>
                </p>
                <div class="hero__actions">
                    <a class="button button--primary" href="<?= site_url('/') ?>"><?= esc(ui_locale() === 'it' ? 'Torna alla home' : 'Back to home') ?></a>
                    <a class="button button--secondary" href="<?= route_url('auth.login') ?>"><?= esc(ui_locale() === 'it' ? 'Apri accesso' : 'Open login') ?></a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p><?= esc(ui_locale() === 'it' ? 'La pagina richiesta non è disponibile o il collegamento non è più valido.' : 'The requested page is not available or the link is no longer valid.') ?></p>
    </footer>
</div>
<script src="<?= base_url('assets/js/app.js') ?>" defer></script>
</body>
</html>
