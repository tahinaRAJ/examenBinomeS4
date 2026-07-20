<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Modifier un préfixe<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Préfixe « <?= esc($prefixe['prefixe']) ?> »<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card" style="max-width: 480px;">
    <h2>Modifier le préfixe</h2>

    <?php if (session()->getFlashdata('errors')) : ?>
        <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
            <p class="form-error"><?= esc($erreur) ?></p>
        <?php endforeach ?>
    <?php endif ?>

    <form method="post" action="<?= site_url('prefixes/' . $prefixe['id']) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="prefixe">Préfixe</label>
            <input type="text" id="prefixe" name="prefixe" value="<?= old('prefixe', $prefixe['prefixe']) ?>" required>
        </div>
        <div class="form-group">
            <label for="idOperateur">Opérateur</label>
            <select id="idOperateur" name="idOperateur" required>
                <?php foreach ($operateurs as $op) : ?>
                    <option value="<?= $op['id'] ?>" <?= old('idOperateur', $prefixe['idOperateur']) == $op['id'] ? 'selected' : '' ?>>
                        <?= esc($op['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a class="btn" href="<?= site_url('prefixes') ?>">Annuler</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
