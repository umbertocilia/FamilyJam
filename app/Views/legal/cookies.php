<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center pt-2 pt-md-4">
    <div class="col-xl-10">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cookie-bite mr-2"></i><?= esc(ui_text('gdpr.cookies.title')) ?></h3>
            </div>
            <div class="card-body">
                <p class="lead"><?= esc(ui_text('gdpr.cookies.lead')) ?></p>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?= esc(ui_text('gdpr.cookies.table.name')) ?></th>
                                <th><?= esc(ui_text('gdpr.cookies.table.category')) ?></th>
                                <th><?= esc(ui_text('gdpr.cookies.table.purpose')) ?></th>
                                <th><?= esc(ui_text('gdpr.cookies.table.duration')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>familyjam_session</code></td>
                                <td><?= esc(ui_text('gdpr.category.necessary')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.row.session')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.duration.session')) ?></td>
                            </tr>
                            <tr>
                                <td><code><?= esc(\App\Services\Legal\PrivacyConsentService::CONSENT_ID_COOKIE) ?></code></td>
                                <td><?= esc(ui_text('gdpr.category.necessary')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.row.consent_id')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.duration.six_months')) ?></td>
                            </tr>
                            <tr>
                                <td><code><?= esc(\App\Services\Legal\PrivacyConsentService::CONSENT_STATE_COOKIE) ?></code></td>
                                <td><?= esc(ui_text('gdpr.category.necessary')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.row.consent_state')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.duration.six_months')) ?></td>
                            </tr>
                            <tr>
                                <td><code>familyjam-theme</code></td>
                                <td><?= esc(ui_text('gdpr.category.preferences')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.row.theme')) ?></td>
                                <td><?= esc(ui_text('gdpr.cookies.duration.browser_local')) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><?= esc(ui_text('gdpr.cookies.categories.title')) ?></h3>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><?= esc(ui_text('gdpr.category.necessary.help')) ?></li>
                            <li><?= esc(ui_text('gdpr.category.preferences.help')) ?></li>
                            <li><?= esc(ui_text('gdpr.category.analytics.help')) ?></li>
                            <li><?= esc(ui_text('gdpr.category.marketing.help')) ?></li>
                        </ul>
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
