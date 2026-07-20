<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Vue d'ensemble de l'activité et des gains de l'opérateur<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Filtre par opérateur -->
<div class="app-card" style="margin-bottom: 20px;">
    <form method="get" action="<?= site_url('dashboard') ?>" class="filter-form">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="operateur">Opérateur</label>
            <select id="operateur" name="operateur" onchange="this.form.submit()">
                <option value="">Tous les opérateurs</option>
                <?php foreach ($operateurs as $op) : ?>
                    <option value="<?= $op['id'] ?>" <?= $idOperateur === (int) $op['id'] ? 'selected' : '' ?>>
                        <?= esc($op['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-sm">Filtrer</button></noscript>
    </form>
    <?php if ($idOperateur !== null) : ?>
        <p class="muted" style="margin-top: 10px;">
            Un mouvement entre deux opérateurs différents (ex. Telma → Orange) compte pour les deux :
            la somme des gains par opérateur peut donc dépasser le total « Tous les opérateurs ».
        </p>
    <?php endif ?>
</div>

<!-- Situation des gains + stats principales -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-label">Gains totaux (frais perçus)</div>
        <div class="stat-value"><?= number_format($statsMouvements['totalGains'], 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Gains du jour</div>
        <div class="stat-value amount-gain"><?= number_format($gainsDuJour, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mouvements</div>
        <div class="stat-value"><?= $statsMouvements['nbMouvements'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Volume échangé</div>
        <div class="stat-value"><?= number_format($statsMouvements['volumeTotal'], 0, ',', ' ') ?> Ar</div>
    </div>
</div>

<?php if ($gainsDetailles !== null) : ?>
    <!-- Point 3 : d'où viennent les gains — interne vs autres opérateurs -->
    <div class="app-card">
        <div class="card-header-row">
            <h2>Situation des gains : chez nous / avec les autres opérateurs</h2>
            <a class="btn btn-sm" href="<?= site_url('compensation?operateur=' . $idOperateur) ?>">Voir la compensation →</a>
        </div>

        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Origine du gain</th>
                        <th>Qui paie</th>
                        <th class="text-right">Montant (Ar)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Frais sur mouvements internes</strong>
                            <div class="muted">dépôts, retraits et envois restés chez nous</div></td>
                        <td class="muted">nos propres clients</td>
                        <td class="text-right amount-gain"><?= number_format($gainsDetailles['fraisInternes'], 0, ',', ' ') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Frais sur envois vers d'autres opérateurs</strong>
                            <div class="muted">nos clients envoient à l'extérieur</div></td>
                        <td class="muted">nos propres clients</td>
                        <td class="text-right amount-gain"><?= number_format($gainsDetailles['fraisSortants'], 0, ',', ' ') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Commissions reçues des autres opérateurs</strong>
                            <div class="muted">argent entrant chez nous depuis l'extérieur</div></td>
                        <td class="muted">les autres opérateurs</td>
                        <td class="text-right amount-gain"><?= number_format($gainsDetailles['commissionsRecues'], 0, ',', ' ') ?></td>
                    </tr>
                    <tr style="border-top: 2px solid var(--border);">
                        <td><strong>Total des gains</strong></td>
                        <td></td>
                        <td class="text-right"><strong><?= number_format($gainsDetailles['total'], 0, ',', ' ') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-card">
                <div class="stat-label">Gains internes</div>
                <div class="stat-value"><?= number_format($gainsDetailles['totalInterne'], 0, ',', ' ') ?> Ar</div>
                <div class="stat-change">activité entre nos clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Gains avec les autres opérateurs</div>
                <div class="stat-value"><?= number_format($gainsDetailles['totalAutres'], 0, ',', ' ') ?> Ar</div>
                <div class="stat-change">frais sortants + commissions reçues</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Commissions versées</div>
                <div class="stat-value amount-loss"><?= number_format($gainsDetailles['commissionsVersees'], 0, ',', ' ') ?> Ar</div>
                <div class="stat-change">payées par nos clients aux autres</div>
            </div>
        </div>

        <p class="muted" style="margin-top: 12px;">
            💡 Les <strong>frais</strong> restent toujours chez l'opérateur d'origine ;
            la <strong>commission</strong> va à l'opérateur qui reçoit l'argent.
        </p>
    </div>
<?php endif ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Comptes clients</div>
        <div class="stat-value"><?= $statsComptes['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Comptes actifs</div>
        <div class="stat-value amount-gain"><?= $statsComptes['actifs'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Comptes désactivés</div>
        <div class="stat-value amount-loss"><?= $statsComptes['inactifs'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Solde cumulé des clients</div>
        <div class="stat-value"><?= number_format($statsComptes['soldeTotal'], 0, ',', ' ') ?> Ar</div>
    </div>
</div>

<div class="app-grid-2">
    <!-- Historique des gains -->
    <div class="app-card">
        <h2>Historique des gains par jour</h2>
        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th class="text-right">Mouvements</th>
                        <th class="text-right">Volume (Ar)</th>
                        <th class="text-right">Gains (Ar)</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($gainsParJour === []) : ?>
                    <tr><td colspan="5"><div class="etat-vide"><div class="etat-vide-titre">Aucun gain pour l'instant</div><div class="etat-vide-aide">Les frais perçus apparaîtront ici.</div></div></td></tr>
                <?php endif ?>
                <?php foreach ($gainsParJour as $g) : ?>
                    <tr>
                        <td><strong><?= esc($g['jour']) ?></strong></td>
                        <td class="text-right"><?= $g['nbMouvements'] ?></td>
                        <td class="text-right"><?= number_format((float) $g['volume'], 0, ',', ' ') ?></td>
                        <td class="text-right amount-gain"><?= number_format((float) $g['gains'], 0, ',', ' ') ?></td>
                        <td class="text-right">
                            <a class="btn btn-sm" href="<?= site_url('dashboard/gains/' . $g['jour']) . ($idOperateur !== null ? '?operateur=' . $idOperateur : '') ?>">Détails</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Répartition par type -->
    <div class="app-card">
        <h2>Répartition par type d'opération</h2>
        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="text-right">Nb</th>
                        <th class="text-right">Gains (Ar)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($statsParType === []) : ?>
                    <tr><td colspan="3"><div class="etat-vide"><div class="etat-vide-titre">Aucun mouvement</div><div class="etat-vide-aide">Rien à répartir pour l'instant.</div></div></td></tr>
                <?php endif ?>
                <?php foreach ($statsParType as $s) : ?>
                    <tr>
                        <td><span class="badge badge-operateur"><?= esc($s['libelleType']) ?></span></td>
                        <td class="text-right"><?= $s['nb'] ?></td>
                        <td class="text-right amount-gain"><?= number_format((float) $s['gains'], 0, ',', ' ') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Comptes clients -->
<div class="app-card">
    <div class="card-header-row">
        <h2>Comptes clients</h2>
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
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
