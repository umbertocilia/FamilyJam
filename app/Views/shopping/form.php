<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
helper('ui');
$membership = $shoppingFormContext['membership'];
$list = is_array($shoppingFormContext['list'] ?? null) ? $shoppingFormContext['list'] : [];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$action = $isEdit ? route_url('shopping.update', $identifier, $list['id']) : route_url('shopping.store', $identifier);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow"><?= esc(ui_locale() === 'it' ? 'Liste spesa' : 'Shopping lists') ?></p>
        <h1><?= esc($isEdit ? (ui_locale() === 'it' ? 'Modifica lista' : 'Edit list') : (ui_locale() === 'it' ? 'Nuova lista spesa' : 'New shopping list')) ?></h1>
        <p class="hero__lead"><?= esc(ui_locale() === 'it' ? 'Pochi campi, focus sulla velocita: nome chiaro e scelta eventuale della lista predefinita.' : 'Few fields, speed-first flow: clear name and optional default-list selection.') ?></p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span><?= esc(ui_locale() === 'it' ? 'Nome lista' : 'List name') ?></span>
                <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name', (string) ($list['name'] ?? ''))) ?>" maxlength="120" required>
            </label>
        </div>

        <label class="checkbox-row">
            <input type="checkbox" name="is_default" value="1" <?= old('is_default', (string) ($list['is_default'] ?? '0')) ? 'checked' : '' ?>>
            <span><?= esc(ui_locale() === 'it' ? 'Usa come lista predefinita della casa' : 'Use as the household default list') ?></span>
        </label>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= esc($isEdit ? (ui_locale() === 'it' ? 'Salva lista' : 'Save list') : (ui_locale() === 'it' ? 'Crea lista' : 'Create list')) ?></button>
            <a class="button button--secondary" href="<?= route_url('shopping.index', $identifier) ?>"><?= esc(ui_locale() === 'it' ? 'Annulla' : 'Cancel') ?></a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
