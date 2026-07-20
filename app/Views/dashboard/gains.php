<?php
    $totalGains  = array_sum(array_map(static fn ($m) => (float) $m['frais'], $mouvements));
    $totalVolume = array_sum(array_map(static fn ($m) => (float) $m['montant'], $mouvements));
?>
<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Gains du <?= esc($jour) ?><?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Détail des mouvements et des frais perçus ce jour-là<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
    $nomOperateurFiltre = null;
    if ($idOperateur !== null) {
        foreach ($operateurs as $op) {
            if ((int) $op['id'] === $idOperateur) {
                $nomOperateurFiltre = $op['nom'];
            }
        }
    }
?>
<?php if ($nomOperateurFiltre !== null) : ?>
    <p class="muted" style="margin-bottom: 16px;">
        Filtré sur l'opérateur : <span class="badge badge-operateur"><?= esc($nomOperateurFiltre) ?></span>
    </p>
<?php endif ?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-label">Gains du <?= esc($jour) ?></div>
        <div class="stat-value"><?= number_format($totalGains, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mouvements</div>
        <div class="stat-value"><?= count($mouvements) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Volume échangé</div>
        <div class="stat-value"><?= number_format($totalVolume, 0, ',', ' ') ?> Ar</div>
    </div>
</div>

<div class="app-card">
    <div class="card-header-row">
        <h2>Mouvements du <?= esc($jour) ?></h2>
        <a class="btn" href="<?= site_url('dashboard') . ($idOperateur !== null ? '?operateur=' . $idOperateur : '') ?>">← Retour au dashboard</a>
    </div>
    <div class="table-scroll">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Type</th>
                    <th>Émetteur</th>
                    <th>Destinataire</th>
                    <th class="text-right">Montant (Ar)</th>
                    <th class="text-right">Frais perçu (Ar)</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($mouvements === []) : ?>
                <tr><td colspan="6"><div class="etat-vide"><div class="etat-vide-titre">Aucun mouvement ce jour-là</div><div class="etat-vide-aide">Choisissez une autre date dans l'historique.</div></div></td></tr>
            <?php endif ?>
            <?php foreach ($mouvements as $m) : ?>
                <tr>
                    <td class="muted"><?= esc(substr($m['dateMouvement'], 11, 8)) ?></td>
                    <td><span class="badge badge-operateur"><?= esc($m['libelleType']) ?></span></td>
                    <td><?= esc($m['numeroSender'] ?? '— (dépôt)') ?></td>
                    <td><?= esc($m['numeroReceiver'] ?? '— (retrait)') ?></td>
                    <td class="text-right"><?= number_format((float) $m['montant'], 0, ',', ' ') ?></td>
                    <td class="text-right amount-gain"><?= number_format((float) $m['frais'], 0, ',', ' ') ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
