<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-lg-5">
        <div class="login-box w-100">
            <div class="card card-outline card-primary">
                <div class="card-header text-center border-bottom-0">
                    <img src="<?= base_url('assets/images/FamilyJamLogo.png') ?>" alt="FamilyJam" class="home-logo mx-auto mb-3">
                    <a href="<?= route_url('home') ?>" class="h1"><b>Family</b>Jam</a>
                </div>
                <div class="card-body pt-0">
                    <p class="login-box-msg">
                        <?= esc(ui_locale() === 'it'
                            ? 'Accedi per gestire spese condivise, chore, shopping list e comunicazione household.'
                            : 'Sign in to manage shared expenses, chores, shopping lists and household communication.') ?>
                    </p>

                    <?= $this->include('auth/_login_form') ?>

                    <p class="mb-0 mt-3 text-center">
                        <a href="<?= route_url('auth.register') ?>" class="text-center">
                            <?= esc(ui_locale() === 'it' ? 'Crea un nuovo account' : 'Create a new account') ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7" id="info-app">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-home mr-2"></i><?= esc(ui_locale() === 'it' ? 'Informazioni app' : 'About the app') ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Spese condivise' : 'Shared expenses') ?></span>
                                <span class="info-box-number"><?= esc(ui_locale() === 'it' ? 'Split semplici e saldo live' : 'Easy splits and live balances') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-check-square"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Chore e routine' : 'Chores and routines') ?></span>
                                <span class="info-box-number"><?= esc(ui_locale() === 'it' ? 'Assegnazioni, rotazioni e reminder' : 'Assignments, rotations and reminders') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light mb-md-0">
                            <span class="info-box-icon bg-warning"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Shopping list' : 'Shopping lists') ?></span>
                                <span class="info-box-number"><?= esc(ui_locale() === 'it' ? 'Liste rapide e conversione in spesa' : 'Fast lists and expense conversion') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light mb-0">
                            <span class="info-box-icon bg-danger"><i class="fas fa-bell"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_locale() === 'it' ? 'Notifiche e comunicazione' : 'Notifications and communication') ?></span>
                                <span class="info-box-number"><?= esc(ui_locale() === 'it' ? 'Bacheca, inviti e aggiornamenti' : 'Pinboard, invites and updates') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
