<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<?php
$membership = $shoppingFormContext['membership'];
$list = is_array($shoppingFormContext['list'] ?? null) ? $shoppingFormContext['list'] : [];
$identifier = (string) ($membership['household_slug'] ?? $membership['household_id']);
$isEdit = $formMode === 'edit';
$action = $isEdit ? route_url('shopping.update', $identifier, $list['id']) : route_url('shopping.store', $identifier);
?>
<section class="panel panel--hero">
    <div class="stack">
        <p class="eyebrow">Shopping Lists</p>
        <h1><?= $isEdit ? 'Modifica lista' : 'Nuova shopping list' ?></h1>
        <p class="hero__lead">Pochi campi, focus sulla velocita: nome chiaro e scelta eventuale della lista default.</p>
    </div>
</section>

<section class="panel">
    <form class="auth-form" method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="field field--full">
                <span>Nome lista</span>
                <input class="<?= esc(field_error_class($formErrors, 'name')) ?>" type="text" name="name" value="<?= esc(old('name', (string) ($list['name'] ?? ''))) ?>" maxlength="120" required>
            </label>
        </div>

        <label class="checkbox-row">
            <input type="checkbox" name="is_default" value="1" <?= old('is_default', (string) ($list['is_default'] ?? '0')) ? 'checked' : '' ?>>
            <span>Usa come lista default household</span>
        </label>

        <div class="hero__actions">
            <button class="button button--primary" type="submit"><?= $isEdit ? 'Save list' : 'Crea lista' ?></button>
            <a class="button button--secondary" href="<?= route_url('shopping.index', $identifier) ?>">Cancel</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
