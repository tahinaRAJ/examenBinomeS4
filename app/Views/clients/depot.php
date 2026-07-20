<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Dépôt<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Alimenter votre compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Effectuer un dépôt</h2>

        <form action="<?= site_url('client/depot') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="montant">Montant à déposer (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="1"
                       placeholder="Ex. 50000" required autofocus>
            </div>

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
        <p class="muted">Le dépôt est une opération non tarifable : aucun frais
        n'est prélevé, la totalité du montant est créditée sur votre compte.</p>
    </div>
</div>

<?= $this->endSection() ?>
