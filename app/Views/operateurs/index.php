<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Opérateurs<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Commission prélevée sur l'argent reçu depuis un autre opérateur<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Opérateurs et commissions</h2>
        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Opérateur</th>
                        <th class="text-right">Commission</th>
                        <th class="text-right">Préfixes</th>
                        <th class="text-right">Tarifs</th>
                        <th class="text-right">Comptes</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($operateurs as $op) : ?>
                    <tr>
                        <td><strong><?= esc($op['nom']) ?></strong></td>
                        <td class="text-right">
                            <?php if ((float) $op['pourcentageCommission'] > 0) : ?>
                                <span class="badge badge-operateur"><?= rtrim(rtrim(number_format((float) $op['pourcentageCommission'], 2, ',', ' '), '0'), ',') ?> %</span>
                            <?php else : ?>
                                <span class="muted">0 %</span>
                            <?php endif ?>
                        </td>
                        <td class="text-right"><?= (int) $op['nbPrefixes'] ?></td>
                        <td class="text-right">
                            <?php if ((int) $op['nbTarifs'] === 0) : ?>
                                <span class="amount-loss" title="Sans grille, tous ses frais valent 0">0 ⚠</span>
                            <?php else : ?>
                                <?= (int) $op['nbTarifs'] ?>
                            <?php endif ?>
                        </td>
                        <td class="text-right"><?= (int) $op['nbComptes'] ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-sm" href="<?= site_url('operateurs/' . $op['id'] . '/edit') ?>">Modifier</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top: 12px;">
            💡 La commission est prélevée <strong>en plus</strong> des frais, uniquement sur les transferts
            venant d'un <strong>autre</strong> opérateur. C'est l'opérateur qui <strong>reçoit</strong>
            l'argent qui l'encaisse, à son propre taux.
        </p>
    </div>

    <div class="app-card">
        <h2>Ajouter un opérateur</h2>

        <?php if (session()->getFlashdata('errors')) : ?>
            <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
                <p class="form-error"><?= esc($erreur) ?></p>
            <?php endforeach ?>
        <?php endif ?>

        <form method="post" action="<?= site_url('operateurs') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= esc(old('nom')) ?>" required>
            </div>
            <div class="form-group">
                <label for="pourcentageCommission">Commission (%) <span class="muted">sur l'argent entrant</span></label>
                <input type="number" step="0.01" min="0" max="100" id="pourcentageCommission"
                       name="pourcentageCommission" value="<?= esc(old('pourcentageCommission')) ?>" placeholder="Ex. 2">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>

        <p class="muted" style="margin-top: 16px;">
            ⚠ Un nouvel opérateur n'a <strong>aucune grille tarifaire</strong> : tous ses frais
            vaudront 0 tant que vous ne lui aurez pas saisi ses tranches dans
            <a href="<?= site_url('tarifs') ?>">Tarifs</a>.
        </p>
    </div>
</div>

<?= $this->endSection() ?>
