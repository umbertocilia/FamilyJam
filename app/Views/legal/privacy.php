<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-xl-10">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i><?= esc(ui_text('gdpr.privacy.title')) ?></h3>
            </div>
            <div class="card-body">
                <p class="lead"><?= esc(ui_text('gdpr.privacy.lead')) ?></p>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_text('gdpr.privacy.processing.title')) ?></span>
                                <span class="text-muted text-sm"><?= esc(ui_text('gdpr.privacy.processing.body')) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-cookie-bite"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= esc(ui_text('gdpr.privacy.cookies.title')) ?></span>
                                <span class="text-muted text-sm"><?= esc(ui_text('gdpr.privacy.cookies.body')) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc(ui_text('gdpr.privacy.bases.title')) ?></h3>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><?= esc(ui_text('gdpr.privacy.bases.contract')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.bases.legitimate')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.bases.consent')) ?></li>
                        </ul>
                    </div>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc(ui_text('gdpr.privacy.rights.title')) ?></h3>
                    </div>
                    <div class="card-body">
                        <p><?= esc(ui_text('gdpr.privacy.rights.body')) ?></p>
                        <ul class="mb-0">
                            <li><?= esc(ui_text('gdpr.privacy.rights.access')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.rights.rectification')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.rights.erasure')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.rights.portability')) ?></li>
                            <li><?= esc(ui_text('gdpr.privacy.rights.objection')) ?></li>
                        </ul>
                    </div>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc(ui_text('gdpr.privacy.retention.title')) ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= esc(ui_text('gdpr.privacy.retention.body')) ?></p>
                    </div>
                </div>

                <div class="mt-3 text-muted text-sm">
                    <?= esc(ui_text('gdpr.policy.version', ['version' => (string) ($legalContext['policyVersion'] ?? '2026.04')])) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
