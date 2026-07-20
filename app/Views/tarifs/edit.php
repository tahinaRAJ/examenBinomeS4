<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Modifier un tarif<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>⚠ Modifier une ligne réécrit l'historique de la grille — pour un changement de tarif, préférez un ajout<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card" style="max-width: 480px;">
    <h2>Modifier le tarif</h2>

    <?php if (session()->getFlashdata('errors')) : ?>
        <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
            <p class="form-error"><?= esc($erreur) ?></p>
        <?php endforeach ?>
    <?php endif ?>

    <form method="post" action="<?= site_url('tarifs/' . $tarif['id']) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="idOperateur">Opérateur</label>
            <select id="idOperateur" name="idOperateur" required>
                <?php foreach ($operateurs as $op) : ?>
                    <option value="<?= $op['id'] ?>" <?= old('idOperateur', $tarif['idOperateur']) == $op['id'] ? 'selected' : '' ?>>
                        <?= esc($op['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label for="idTypeMouvement">Type d'opération</label>
            <select id="idTypeMouvement" name="idTypeMouvement" required>
                <?php foreach ($types as $type) : ?>
                    <option value="<?= $type['id'] ?>" <?= old('idTypeMouvement', $tarif['idTypeMouvement']) == $type['id'] ? 'selected' : '' ?>>
                        <?= esc($type['libelle']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label for="minMontant">Montant minimum (Ar)</label>
            <input type="number" step="any" min="0" id="minMontant" name="minMontant" value="<?= old('minMontant', $tarif['minMontant']) ?>" required>
        </div>
        <div class="form-group">
            <label for="maxMontant">Montant maximum (Ar)</label>
            <input type="number" step="any" min="0" id="maxMontant" name="maxMontant" value="<?= old('maxMontant', $tarif['maxMontant']) ?>" required>
        </div>
        <div class="form-group">
            <label for="montantFrais">Frais (Ar)</label>
            <input type="number" step="any" min="0" id="montantFrais" name="montantFrais" value="<?= old('montantFrais', $tarif['montantFrais']) ?>" required>
        </div>
        <div class="form-group">
            <label for="dateFrais">Date d'entrée en vigueur</label>
            <input type="datetime-local" id="dateFrais" name="dateFrais"
                   value="<?= old('dateFrais', str_replace(' ', 'T', substr($tarif['dateFrais'], 0, 16))) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a class="btn" href="<?= site_url('tarifs') ?>">Annuler</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
