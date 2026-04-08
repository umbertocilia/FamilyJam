<?php
$privacyConsent = $privacyConsent ?? service('privacyConsent')->viewContext($currentUserId ?? null);
$cookieLinks = $privacyConsent['links'];
$cookieState = $privacyConsent['state'];
$cookieCategories = $privacyConsent['categories'];
$returnUrl = current_url(true)->__toString();
?>
<?php if ((bool) ($privacyConsent['bannerRequired'] ?? false)): ?>
    <div class="cookie-banner card card-outline card-info shadow-sm" data-cookie-banner>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="card-title font-weight-bold mb-2"><?= esc(ui_text('gdpr.banner.title')) ?></h3>
                    <p class="text-sm text-muted mb-2"><?= esc(ui_text('gdpr.banner.body')) ?></p>
                    <p class="mb-0 text-sm">
                        <a href="<?= esc((string) $cookieLinks['privacy']) ?>"><?= esc(ui_text('gdpr.link.privacy')) ?></a>
                        <span class="mx-1">|</span>
                        <a href="<?= esc((string) $cookieLinks['cookies']) ?>"><?= esc(ui_text('gdpr.link.cookies')) ?></a>
                    </p>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="d-flex flex-column flex-md-row justify-content-lg-end">
                        <form method="post" action="<?= esc((string) $cookieLinks['essential']) ?>" class="mr-md-2 mb-2 mb-md-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_url" value="<?= esc($returnUrl) ?>">
                            <button type="submit" class="btn btn-default btn-block"><?= esc(ui_text('gdpr.banner.essential')) ?></button>
                        </form>
                        <button type="button" class="btn btn-outline-info mr-md-2 mb-2 mb-md-0" data-toggle="modal" data-target="#cookiePreferencesModal">
                            <?= esc(ui_text('gdpr.banner.manage')) ?>
                        </button>
                        <form method="post" action="<?= esc((string) $cookieLinks['acceptAll']) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_url" value="<?= esc($returnUrl) ?>">
                            <button type="submit" class="btn btn-info btn-block"><?= esc(ui_text('gdpr.banner.accept_all')) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="cookiePreferencesModal" tabindex="-1" role="dialog" aria-labelledby="cookiePreferencesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form method="post" action="<?= esc((string) $cookieLinks['save']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="return_url" value="<?= esc($returnUrl) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="cookiePreferencesModalLabel"><?= esc(ui_text('gdpr.modal.title')) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= esc(ui_text('common.close')) ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><?= esc(ui_text('gdpr.modal.body')) ?></p>

                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><?= esc(ui_text('gdpr.category.necessary')) ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="mb-2 text-sm text-muted"><?= esc(ui_text('gdpr.category.necessary.help')) ?></p>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="cookieNecessary" checked disabled>
                                <label class="custom-control-label" for="cookieNecessary"><?= esc(ui_text('gdpr.category.always_on')) ?></label>
                            </div>
                        </div>
                    </div>

                    <?php foreach (['preferences', 'analytics', 'marketing'] as $categoryKey): ?>
                        <div class="card card-outline card-light">
                            <div class="card-header">
                                <h3 class="card-title"><?= esc(ui_text('gdpr.category.' . $categoryKey)) ?></h3>
                            </div>
                            <div class="card-body">
                                <p class="mb-2 text-sm text-muted"><?= esc(ui_text('gdpr.category.' . $categoryKey . '.help')) ?></p>
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="cookieConsent<?= esc(ucfirst($categoryKey)) ?>"
                                        name="<?= esc($categoryKey) ?>"
                                        value="1"
                                        <?= ! empty($cookieCategories[$categoryKey]['enabled']) || ! empty($cookieState[$categoryKey]) ? 'checked' : '' ?>
                                    >
                                    <label class="custom-control-label" for="cookieConsent<?= esc(ucfirst($categoryKey)) ?>">
                                        <?= esc(ui_text('gdpr.category.enable')) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer justify-content-between">
                    <a class="btn btn-link pl-0" href="<?= esc((string) $cookieLinks['cookies']) ?>"><?= esc(ui_text('gdpr.link.cookies')) ?></a>
                    <div class="d-flex flex-wrap">
                        <button type="button" class="btn btn-default mr-2 mb-2 mb-sm-0" data-dismiss="modal"><?= esc(ui_text('common.cancel')) ?></button>
                        <button type="submit" class="btn btn-primary"><?= esc(ui_text('gdpr.modal.save')) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
