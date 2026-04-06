<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php helper('ui'); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h1><?= esc(ui_locale() === 'it' ? 'Le tue household' : 'Your households') ?></h1>
    </div>
    <div class="col-sm-6">
        <div class="float-sm-right d-flex flex-wrap gap-2 justify-content-sm-end">
            <a class="btn btn-primary" href="<?= route_url('households.create') ?>">
                <i class="fas fa-plus mr-1"></i><?= esc(ui_locale() === 'it' ? 'Nuova household' : 'New household') ?>
            </a>
            <a class="btn btn-default" href="<?= route_url('households.switcher') ?>">
                <i class="fas fa-exchange-alt mr-1"></i><?= esc(ui_locale() === 'it' ? 'Cambia household' : 'Switch household') ?>
            </a>
        </div>
    </div>
</div>

<?php if ($households === []): ?>
    <div class="card card-outline card-primary">
        <div class="card-body">
            <?php
            $title = ui_locale() === 'it' ? 'Nessuna household disponibile' : 'No household available';
            $message = ui_locale() === 'it'
                ? 'Crea il primo workspace per iniziare a gestire membri, ruoli, spese e notifiche.'
                : 'Create the first workspace to start managing members, roles, expenses and notifications.';
            $actionLabel = ui_locale() === 'it' ? 'Crea household' : 'Create household';
            $actionHref = route_url('households.create');
            $icon = 'fas fa-home';
            ?>
            <?= $this->include('partials/components/empty_state') ?>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($households as $household): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-outline card-primary h-100">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc((string) $household['household_name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge badge-info"><?= esc((string) ($household['role_codes'] ?? 'member')) ?></span>
                            <span class="badge badge-secondary"><?= esc((string) $household['status']) ?></span>
                        </div>
                        <p class="text-muted mb-3">
                            <?= esc(ui_locale() === 'it' ? 'Workspace separato con membri, permessi, spese e impostazioni dedicate.' : 'Separated workspace with dedicated members, permissions, expenses and settings.') ?>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary" href="<?= route_url('households.dashboard', $household['household_slug']) ?>">
                                <i class="fas fa-tachometer-alt mr-1"></i><?= esc(ui_text('common.open')) ?>
                            </a>
                            <form method="post" action="<?= route_url('households.switch', $household['household_slug']) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button class="btn btn-default" type="submit">
                                    <i class="fas fa-check mr-1"></i><?= esc(ui_locale() === 'it' ? 'Imposta attiva' : 'Set current') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
