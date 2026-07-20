<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Détail de la compensation<?= $this->endSection() ?>
<?= $this->section('subtitle') ?><?= esc($operateur['nom']) ?> ↔ <?= esc($autre['nom']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-card">
    <div class="card-header-row">
        <h2>Mouvements échangés (<?= count($mouvements) ?>)</h2>
        <a class="btn" href="<?= site_url('compensation?operateur=' . $operateur['id']) ?>">← Retour</a>
    </div>

    <div class="table-scroll">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sens</th>
                    <th>De → Vers</th>
                    <th class="text-right">Montant</th>
                    <th class="text-right">Commission</th>
                    <th class="text-right">Total dû</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($mouvements === []) : ?>
                <tr><td colspan="6" class="muted">Aucun mouvement échangé avec cet opérateur.</td></tr>
            <?php endif ?>
            <?php foreach ($mouvements as $m) : ?>
                <?php $sortant = (int) $m['opSender'] === (int) $operateur['id']; ?>
                <tr>
                    <td class="muted"><?= esc($m['dateMouvement']) ?></td>
                    <td>
                        <?php if ($sortant) : ?>
                            <span class="amount-loss">Nous devons</span>
                        <?php else : ?>
                            <span class="amount-gain">Il nous doit</span>
                        <?php endif ?>
                    </td>
                    <td><?= esc($m['numeroSender']) ?> → <?= esc($m['numeroReceiver']) ?></td>
                    <td class="text-right"><?= number_format((float) $m['montant'], 0, ',', ' ') ?></td>
                    <td class="text-right"><?= number_format((float) $m['commission'], 0, ',', ' ') ?></td>
                    <td class="text-right <?= $sortant ? 'amount-loss' : 'amount-gain' ?>">
                        <strong><?= number_format((float) $m['montant'] + (float) $m['commission'], 0, ',', ' ') ?></strong>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top: 12px;">
        Le <strong>frais</strong> de chaque transfert n'apparaît pas ici : il est gardé par
        l'opérateur d'origine et n'entre donc pas dans la compensation.
    </p>
</div>

<?= $this->endSection() ?>
