<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Compte <?= esc($compte['numero']) ?><?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Détails du compte de <?= esc($compte['nom']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-label">Solde</div>
        <div class="stat-value"><?= number_format((float) $compte['solde'], 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Opérateur</div>
        <div class="stat-value"><?= esc($compte['nomOperateur']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Statut</div>
        <div class="stat-value">
            <?php if ((int) $compte['estActif'] === 1) : ?>
                <span class="badge badge-actif">Actif</span>
            <?php else : ?>
                <span class="badge badge-inactif">Désactivé</span>
            <?php endif ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Créé le</div>
        <div class="stat-value" style="font-size: 18px;"><?= esc($compte['created_at']) ?></div>
    </div>
</div>

<div class="app-card">
    <div class="card-header-row">
        <h2>Mouvements du compte</h2>
        <div>
            <a class="btn" href="<?= site_url('comptes/' . $compte['id'] . '/edit') ?>">Modifier le compte</a>
            <a class="btn" href="<?= site_url('comptes') ?>">← Retour</a>
        </div>
    </div>
    <div class="table-scroll">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Sens</th>
                    <th>Contrepartie</th>
                    <th class="text-right">Montant (Ar)</th>
                    <th class="text-right">Frais (Ar)</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($mouvements === []) : ?>
                <tr><td colspan="6"><div class="etat-vide"><div class="etat-vide-titre">Aucun mouvement</div><div class="etat-vide-aide">Ce compte n'a encore aucune opération.</div></div></td></tr>
            <?php endif ?>
            <?php foreach ($mouvements as $m) : ?>
                <?php $estEmis = (int) $m['idSender'] === (int) $compte['id']; ?>
                <tr>
                    <td class="muted"><?= esc($m['dateMouvement']) ?></td>
                    <td><span class="badge badge-operateur"><?= esc($m['libelleType']) ?></span></td>
                    <td>
                        <?php if ($estEmis) : ?>
                            <span class="amount-loss">Sortant</span>
                        <?php else : ?>
                            <span class="amount-gain">Entrant</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($estEmis) : ?>
                            <?= esc($m['numeroReceiver'] ?? '—') ?>
                        <?php else : ?>
                            <?= esc($m['numeroSender'] ?? '—') ?>
                        <?php endif ?>
                    </td>
                    <td class="text-right <?= $estEmis ? 'amount-loss' : 'amount-gain' ?>">
                        <?= $estEmis ? '-' : '+' ?><?= number_format((float) $m['montant'], 0, ',', ' ') ?>
                    </td>
                    <td class="text-right"><?= number_format((float) $m['frais'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
