<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Comptes clients<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Gestion des comptes des clients<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Comptes</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Actifs</div>
        <div class="stat-value amount-gain"><?= $stats['actifs'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Désactivés</div>
        <div class="stat-value amount-loss"><?= $stats['inactifs'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Solde cumulé</div>
        <div class="stat-value"><?= number_format($stats['soldeTotal'], 0, ',', ' ') ?> Ar</div>
    </div>
</div>

<div class="app-card">
    <div class="card-header-row">
        <h2>Liste des comptes</h2>
        <a class="btn btn-primary" href="<?= site_url('comptes/create') ?>">+ Nouveau compte</a>
    </div>
    <div class="table-scroll">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Nom</th>
                    <th>Opérateur</th>
                    <th class="text-right">Solde (Ar)</th>
                    <th>Statut</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($comptes === []) : ?>
                <tr><td colspan="6"><div class="etat-vide"><div class="etat-vide-titre">Aucun compte client</div><div class="etat-vide-aide">Créez un compte pour commencer.</div></div></td></tr>
            <?php endif ?>
            <?php foreach ($comptes as $c) : ?>
                <tr>
                    <td><strong><?= esc($c['numero']) ?></strong></td>
                    <td><?= esc($c['nom']) ?></td>
                    <td><span class="badge badge-operateur"><?= esc($c['nomOperateur']) ?></span></td>
                    <td class="text-right"><?= number_format((float) $c['solde'], 0, ',', ' ') ?></td>
                    <td>
                        <?php if ((int) $c['estActif'] === 1) : ?>
                            <span class="badge badge-actif">Actif</span>
                        <?php else : ?>
                            <span class="badge badge-inactif">Désactivé</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm" href="<?= site_url('comptes/' . $c['id']) ?>">Détails</a>
                            <a class="btn btn-sm" href="<?= site_url('comptes/' . $c['id'] . '/edit') ?>">Modifier</a>
                            <form method="post" action="<?= site_url('comptes/' . $c['id'] . '/toggle') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm <?= (int) $c['estActif'] === 1 ? 'btn-danger' : '' ?>">
                                    <?= (int) $c['estActif'] === 1 ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
