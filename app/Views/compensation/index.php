<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Compensation<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Montants à envoyer à chaque opérateur (montant + commission qui lui revient)<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$totalAVerser   = 0.0;
$totalARecevoir = 0.0;
foreach ($situation as $s) {
    $totalAVerser   += $s['aVerser'];
    $totalARecevoir += $s['aRecevoir'];
}
$net = $totalAVerser - $totalARecevoir;
?>

<div class="app-card">
    <form method="get" action="<?= site_url('compensation') ?>" class="filter-form">
        <div class="form-group">
            <label for="operateur">Situation de l'opérateur</label>
            <select id="operateur" name="operateur" onchange="this.form.submit()">
                <?php foreach ($operateurs as $op) : ?>
                    <option value="<?= $op['id'] ?>" <?= $idOperateur === (int) $op['id'] ? 'selected' : '' ?>>
                        <?= esc($op['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-sm">Afficher</button></noscript>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total à verser</div>
        <div class="stat-value amount-loss"><?= number_format($totalAVerser, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total à recevoir</div>
        <div class="stat-value amount-gain"><?= number_format($totalARecevoir, 0, ',', ' ') ?> Ar</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-label">Solde net</div>
        <div class="stat-value">
            <?php if ($net > 0) : ?>
                −<?= number_format($net, 0, ',', ' ') ?> Ar
            <?php elseif ($net < 0) : ?>
                +<?= number_format(-$net, 0, ',', ' ') ?> Ar
            <?php else : ?>
                0 Ar
            <?php endif ?>
        </div>
        <div class="stat-change">
            <?php if ($net > 0) : ?>
                à décaisser au total
            <?php elseif ($net < 0) : ?>
                à encaisser au total
            <?php else : ?>
                équilibré
            <?php endif ?>
        </div>
    </div>
</div>

<div class="app-card">
    <h2>Situation par opérateur</h2>
    <div class="table-scroll">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Opérateur</th>
                    <th class="text-right">À lui verser</th>
                    <th class="text-right">Il nous doit</th>
                    <th class="text-right">Solde net</th>
                    <th>Conclusion</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($situation === []) : ?>
                <tr><td colspan="6" class="muted">Aucun autre opérateur enregistré.</td></tr>
            <?php endif ?>
            <?php foreach ($situation as $s) : ?>
                <tr>
                    <td><strong><?= esc($s['nomAutre']) ?></strong></td>
                    <td class="text-right">
                        <?= number_format($s['aVerser'], 0, ',', ' ') ?>
                        <span class="muted">(<?= $s['nbEnvoyes'] ?>)</span>
                    </td>
                    <td class="text-right">
                        <?= number_format($s['aRecevoir'], 0, ',', ' ') ?>
                        <span class="muted">(<?= $s['nbRecus'] ?>)</span>
                    </td>
                    <td class="text-right <?= $s['net'] > 0 ? 'amount-loss' : ($s['net'] < 0 ? 'amount-gain' : '') ?>">
                        <strong><?= number_format(abs($s['net']), 0, ',', ' ') ?></strong>
                    </td>
                    <td>
                        <?php if ($s['net'] > 0) : ?>
                            <span class="badge badge-inactif">à lui envoyer</span>
                        <?php elseif ($s['net'] < 0) : ?>
                            <span class="badge badge-actif">à recevoir de lui</span>
                        <?php else : ?>
                            <span class="muted">équilibré</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm" href="<?= site_url('compensation/' . $s['idAutre'] . '/details?operateur=' . $idOperateur) ?>">Détails</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top: 12px;">
        💡 Le montant à verser = <strong>montant transféré + commission</strong> revenant à l'autre opérateur.
        Les <strong>frais</strong> ne sont jamais reversés : ils restent chez l'opérateur d'origine.
    </p>
</div>

<?= $this->endSection() ?>
