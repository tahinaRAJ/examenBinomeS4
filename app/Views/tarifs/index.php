<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Tarifs<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Chaque opérateur a sa propre grille — l'historique des tarifs est conservé grâce à la date<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card">
    <form method="get" action="<?= site_url('tarifs') ?>" class="filter-form">
        <div class="form-group">
            <label for="operateur">Filtrer par opérateur</label>
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
</div>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Grille tarifaire (du plus récent au plus ancien)</h2>
        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Opérateur</th>
                        <th>Type d'opération</th>
                        <th class="text-right">Tranche (Ar)</th>
                        <th class="text-right">Frais (Ar)</th>
                        <th>En vigueur depuis</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($tarifs === []) : ?>
                    <tr><td colspan="6"><div class="etat-vide"><div class="etat-vide-titre">Aucun tarif</div><div class="etat-vide-aide">Ajoutez une tranche avec le formulaire ci-contre.</div></div></td></tr>
                <?php endif ?>
                <?php foreach ($tarifs as $t) : ?>
                    <tr>
                        <td><strong><?= esc($t['nomOperateur']) ?></strong></td>
                        <td><span class="badge badge-operateur"><?= esc($t['libelleType']) ?></span></td>
                        <td class="text-right">
                            <?= number_format((float) $t['minMontant'], 0, ',', ' ') ?>
                            —
                            <?= number_format((float) $t['maxMontant'], 0, ',', ' ') ?>
                        </td>
                        <td class="text-right"><strong><?= number_format((float) $t['montantFrais'], 0, ',', ' ') ?></strong></td>
                        <td class="muted"><?= esc($t['dateFrais']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-sm" href="<?= site_url('tarifs/' . $t['id'] . '/edit') ?>">Modifier</a>
                                <form method="post" action="<?= site_url('tarifs/' . $t['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Supprimer ce tarif ?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="muted" style="margin-top: 12px;">
            💡 Pour changer un tarif sans perdre l'historique, préférez <strong>ajouter</strong> une nouvelle
            ligne avec une date d'entrée en vigueur : les mouvements passés gardent leur frais figé.
        </p>
    </div>

    <div>
    <div class="app-card">
        <h2>Simulateur</h2>
        <p class="muted" style="margin-bottom: 14px;">
            Vérifiez le frais appliqué pour un montant donné, sans effectuer de mouvement.
        </p>

        <form id="simulateurTarif" onsubmit="return false;" data-url="<?= site_url('tarifs/simuler') ?>">
            <div class="form-group">
                <label for="simOperateur">Opérateur</label>
                <select id="simOperateur" name="idOperateur">
                    <?php foreach ($operateurs as $op) : ?>
                        <option value="<?= $op['id'] ?>" <?= $idOperateur === (int) $op['id'] ? 'selected' : '' ?>>
                            <?= esc($op['nom']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="simType">Type d'opération</label>
                <select id="simType" name="idTypeMouvement">
                    <?php foreach ($types as $type) : ?>
                        <option value="<?= $type['id'] ?>"><?= esc($type['libelle']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="simMontant">Montant (Ar)</label>
                <input type="number" id="simMontant" name="montant" min="1" step="1" placeholder="Ex. 25000" inputmode="numeric">
            </div>
        </form>

        <div id="resultatSimulateur" class="apercu"></div>
    </div>

    <div class="app-card">
        <h2>Ajouter un tarif</h2>

        <?php if (session()->getFlashdata('errors')) : ?>
            <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
                <p class="form-error"><?= esc($erreur) ?></p>
            <?php endforeach ?>
        <?php endif ?>

        <form method="post" action="<?= site_url('tarifs') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="idOperateur">Opérateur</label>
                <select id="idOperateur" name="idOperateur" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($operateurs as $op) : ?>
                        <option value="<?= $op['id'] ?>"
                            <?= (old('idOperateur') ?? $idOperateur) == $op['id'] ? 'selected' : '' ?>>
                            <?= esc($op['nom']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="idTypeMouvement">Type d'opération</label>
                <select id="idTypeMouvement" name="idTypeMouvement" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($types as $type) : ?>
                        <option value="<?= $type['id'] ?>" <?= old('idTypeMouvement') == $type['id'] ? 'selected' : '' ?>>
                            <?= esc($type['libelle']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="minMontant">Montant minimum (Ar)</label>
                <input type="number" step="any" min="0" id="minMontant" name="minMontant" value="<?= old('minMontant') ?>" required>
            </div>
            <div class="form-group">
                <label for="maxMontant">Montant maximum (Ar)</label>
                <input type="number" step="any" min="0" id="maxMontant" name="maxMontant" value="<?= old('maxMontant') ?>" required>
            </div>
            <div class="form-group">
                <label for="montantFrais">Frais (Ar)</label>
                <input type="number" step="any" min="0" id="montantFrais" name="montantFrais" value="<?= old('montantFrais') ?>" required>
            </div>
            <div class="form-group">
                <label for="dateFrais">Date d'entrée en vigueur <span class="muted">(vide = maintenant)</span></label>
                <input type="datetime-local" id="dateFrais" name="dateFrais" value="<?= old('dateFrais') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/simulateur-tarif.js') ?>"></script>
<?= $this->endSection() ?>
