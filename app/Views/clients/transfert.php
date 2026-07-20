<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Transfert<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Envoyer de l'argent depuis le compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Effectuer un transfert</h2>

        <form action="<?= site_url('client/transfert') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="numeroReceiver">Numéro du destinataire</label>
                <input type="text" id="numeroReceiver" name="numeroReceiver"
                       placeholder="Ex. 0344455667" required autofocus>
            </div>

            <div class="form-group">
                <label for="montant">Montant à envoyer (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="1"
                       placeholder="Ex. 10000" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Valider le transfert</button>
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
        <p class="muted">L'envoi est tarifable : des <strong>frais</strong>
        s'ajoutent au montant transféré. Le destinataire reçoit le montant
        exact, les frais sont prélevés sur votre solde.</p>
    </div>
</div>

<?= $this->endSection() ?>
