<footer class="main-footer">
    <strong>FamilyJam</strong>
    <span class="text-muted"><?= esc(ui_locale() === 'it' ? 'Workspace casa su CodeIgniter 4' : 'Household workspace on CodeIgniter 4') ?></span>
    <span class="ml-3 text-sm">
        <a href="<?= route_url('legal.privacy') ?>"><?= esc(ui_text('gdpr.link.privacy')) ?></a>
        <span class="mx-1">|</span>
        <a href="<?= route_url('legal.cookies') ?>"><?= esc(ui_text('gdpr.link.cookies')) ?></a>
        <span class="mx-1">|</span>
        <a href="#" data-toggle="modal" data-target="#cookiePreferencesModal"><?= esc(ui_text('gdpr.link.manage')) ?></a>
    </span>
    <div class="float-right d-none d-sm-inline-block">
        <b>AdminLTE</b> v3
    </div>
</footer>
