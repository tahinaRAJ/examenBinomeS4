<?php $estEdition = $compte !== null; ?>
<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?><?= $estEdition ? 'Modifier un compte' : 'Nouveau compte' ?><?= $this->endSection() ?>
<?= $this->section('subtitle') ?><?= $estEdition ? 'Compte ' . esc($compte['numero']) : 'Création d\'un compte client' ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card" style="max-width: 480px;">
    <h2><?= $estEdition ? 'Modifier le compte' : 'Créer le compte' ?></h2>

    <?php if (session()->getFlashdata('errors')) : ?>
        <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
            <p class="form-error"><?= esc($erreur) ?></p>
        <?php endforeach ?>
    <?php endif ?>

    <form method="post" action="<?= $estEdition ? site_url('comptes/' . $compte['id']) : site_url('comptes') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="numero">Numéro (10 chiffres)</label>
            <input type="text" id="numero" name="numero" placeholder="0341234567" maxlength="10"
                   value="<?= old('numero', $compte['numero'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="nom">Nom du client</label>
            <input type="text" id="nom" name="nom" value="<?= old('nom', $compte['nom'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="solde">Solde (Ar)</label>
            <input type="number" step="any" min="0" id="solde" name="solde" value="<?= old('solde', $compte['solde'] ?? 0) ?>">
        </div>
        <div class="form-group">
            <label for="idOperateur">Opérateur</label>
            <select id="idOperateur" name="idOperateur" required>
                <option value="">— Choisir —</option>
                <?php foreach ($operateurs as $op) : ?>
                    <option value="<?= $op['id'] ?>" <?= old('idOperateur', $compte['idOperateur'] ?? '') == $op['id'] ? 'selected' : '' ?>>
                        <?= esc($op['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="estActif" value="1" style="width: auto;"
                    <?= old('estActif', $compte['estActif'] ?? 1) ? 'checked' : '' ?>>
                Compte actif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $estEdition ? 'Enregistrer' : 'Créer' ?></button>
            <a class="btn" href="<?= site_url('comptes') ?>">Annuler</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
