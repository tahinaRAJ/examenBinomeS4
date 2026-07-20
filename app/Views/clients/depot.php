<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Dépôt<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Alimenter votre compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Effectuer un dépôt</h2>

        <form action="<?= site_url('client/depot') ?>" method="post"
              data-simulation="depot"
              data-url="<?= site_url('client/simuler/depot') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="montant">Montant à déposer (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="1"
                       placeholder="Ex. 50000" required autofocus inputmode="numeric">
            </div>

            <!-- Aperçu en direct : rempli par apercu-operation.js -->
            <div id="apercuOperation" class="apercu"></div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Valider le dépôt</button>
                <a class="btn" href="<?= site_url('profil') ?>">Annuler</a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <h2>Votre solde</h2>
        <div class="stat-card primary" style="margin-bottom: 16px;">
            <div class="stat-label">Solde actuel</div>
            <div class="stat-value"><?= number_format((float) $client['solde'], 0, ',', ' ') ?> Ar</div>
        </div>

        <div class="info-bloc">
            <strong>Le dépôt est gratuit.</strong><br>
            Aucun frais n'est prélevé : la totalité du montant est créditée sur votre compte.
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/apercu-operation.js') ?>"></script>
<?= $this->endSection() ?>
