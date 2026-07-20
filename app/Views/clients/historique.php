<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Historique<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Toutes les opérations du compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Totaux calculés sur les mouvements déjà chargés (aucune requête ici :
// la vue ne parle jamais à la base).
$totalEntrant = 0.0;
$totalSortant = 0.0;
$totalFrais   = 0.0;

foreach ($mouvements as $m) {
    if ((int) $m['idSender'] === (int) $client['id']) {
        $totalSortant += (float) $m['montant'];
        $totalFrais   += (float) $m['frais'];
    } else {
        $totalEntrant += (float) $m['montant'];
    }
}
?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-label">Solde actuel</div>
        <div class="stat-value"><?= number_format((float) $client['solde'], 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total reçu</div>
        <div class="stat-value amount-gain">+<?= number_format($totalEntrant, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total envoyé / retiré</div>
        <div class="stat-value amount-loss">-<?= number_format($totalSortant, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Frais payés</div>
        <div class="stat-value"><?= number_format($totalFrais, 0, ',', ' ') ?> Ar</div>
    </div>
</div>

<div class="app-card">
    <div class="card-header-row">
        <h2>Mes mouvements (<?= count($mouvements) ?>)</h2>
        <a class="btn" href="<?= site_url('profil') ?>">← Mon profil</a>
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
                <tr><td colspan="6" class="muted">Aucune opération pour le moment.</td></tr>
            <?php endif ?>
            <?php foreach ($mouvements as $m) : ?>
                <?php $estEmis = (int) $m['idSender'] === (int) $client['id']; ?>
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
