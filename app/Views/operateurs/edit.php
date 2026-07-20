<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Modifier un opérateur<?= $this->endSection() ?>
<?= $this->section('subtitle') ?><?= esc($operateur['nom']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card" style="max-width: 480px;">
    <h2>Modifier l'opérateur</h2>

    <?php if (session()->getFlashdata('errors')) : ?>
        <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
            <p class="form-error"><?= esc($erreur) ?></p>
        <?php endforeach ?>
    <?php endif ?>

    <form method="post" action="<?= site_url('operateurs/' . $operateur['id']) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= esc(old('nom', $operateur['nom'])) ?>" required>
        </div>
        <div class="form-group">
            <label for="pourcentageCommission">Commission (%) <span class="muted">sur l'argent reçu d'un autre opérateur</span></label>
            <input type="number" step="0.01" min="0" max="100" id="pourcentageCommission" name="pourcentageCommission"
                   value="<?= esc(old('pourcentageCommission', $operateur['pourcentageCommission'])) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a class="btn" href="<?= site_url('operateurs') ?>">Annuler</a>
        </div>
    </form>

    <p class="muted" style="margin-top: 16px;">
        ⚠ Modifier ce taux ne change <strong>aucun mouvement passé</strong> : la commission
        est figée dans chaque mouvement au moment où il est effectué.
    </p>
</div>

<?= $this->endSection() ?>
