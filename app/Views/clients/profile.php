<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Mon profil<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Bonjour <?= esc($client['nom']) ?>, voici l'état de votre compte.<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-label">Solde disponible</div>
        <div class="stat-value"><?= number_format((float) $client['solde'], 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Numéro de compte</div>
        <div class="stat-value" style="font-size: 22px;"><?= esc($client['numero']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Statut</div>
        <div class="stat-value">
            <?php if ((int) $client['estActif'] === 1) : ?>
                <span class="badge badge-actif">Actif</span>
            <?php else : ?>
                <span class="badge badge-inactif">Désactivé</span>
            <?php endif ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Compte ouvert le</div>
        <div class="stat-value" style="font-size: 18px;"><?= esc($client['created_at']) ?></div>
    </div>
</div>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Mes informations</h2>
        <table class="app-table">
            <tbody>
                <tr>
                    <td class="muted">Nom</td>
                    <td class="text-right"><?= esc($client['nom']) ?></td>
                </tr>
                <tr>
                    <td class="muted">Numéro</td>
                    <td class="text-right"><?= esc($client['numero']) ?></td>
                </tr>
                <tr>
                    <td class="muted">Solde</td>
                    <td class="text-right amount-gain"><?= number_format((float) $client['solde'], 0, ',', ' ') ?> Ar</td>
                </tr>
                <tr>
                    <td class="muted">Dernière mise à jour</td>
                    <td class="text-right"><?= esc($client['updated_at']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="app-card">
        <h2>Opérations rapides</h2>
        <div class="form-actions" style="flex-direction: column;">
            <a class="btn btn-primary" href="<?= site_url('client/depot') ?>" style="text-align: center;">Faire un dépôt</a>
            <a class="btn" href="<?= site_url('client/retrait') ?>" style="text-align: center;">Faire un retrait</a>
            <a class="btn" href="<?= site_url('client/transfert') ?>" style="text-align: center;">Faire un transfert</a>
            <a class="btn" href="<?= site_url('historique') ?>" style="text-align: center;">Voir l'historique</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
