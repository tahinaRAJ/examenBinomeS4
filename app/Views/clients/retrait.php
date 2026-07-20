<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Retrait<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Retirer de l'argent du compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Effectuer un retrait</h2>

        <form action="<?= site_url('client/retrait') ?>" method="post"
              data-simulation="retrait"
              data-url="<?= site_url('client/simuler/retrait') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="montant">Montant à retirer (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="1"
                       placeholder="Ex. 20000" required autofocus inputmode="numeric">
            </div>

            <!-- Aperçu en direct : rempli par apercu-operation.js -->
            <div id="apercuOperation" class="apercu"></div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Valider le retrait</button>
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
            <strong>Le retrait est tarifable.</strong><br>
            Des frais s'ajoutent au montant retiré, selon la tranche dans laquelle il tombe
            et selon la grille de votre opérateur.<br>
            Votre solde doit couvrir le montant <em>et</em> les frais.
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/apercu-operation.js') ?>"></script>
<?= $this->endSection() ?>
