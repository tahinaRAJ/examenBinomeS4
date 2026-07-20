<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Préfixes<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Gestion des préfixes de notre opérateur<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Liste des préfixes</h2>
        <div class="table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Préfixe</th>
                        <th>Opérateur</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($prefixes === []) : ?>
                    <tr><td colspan="3" class="muted">Aucun préfixe enregistré.</td></tr>
                <?php endif ?>
                <?php foreach ($prefixes as $p) : ?>
                    <tr>
                        <td><strong><?= esc($p['prefixe']) ?></strong></td>
                        <td><span class="badge badge-operateur"><?= esc($p['nomOperateur']) ?></span></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-sm" href="<?= site_url('prefixes/' . $p['id'] . '/edit') ?>">Modifier</a>
                                <form method="post" action="<?= site_url('prefixes/' . $p['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Supprimer le préfixe <?= esc($p['prefixe'], 'js') ?> ?');">
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
    </div>

    <div class="app-card">
        <h2>Ajouter un préfixe</h2>

        <?php if (session()->getFlashdata('errors')) : ?>
            <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
                <p class="form-error"><?= esc($erreur) ?></p>
            <?php endforeach ?>
        <?php endif ?>

        <form method="post" action="<?= site_url('prefixes') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="prefixe">Préfixe</label>
                <input type="text" id="prefixe" name="prefixe" placeholder="034" value="<?= old('prefixe') ?>" required>
            </div>
            <div class="form-group">
                <label for="idOperateur">Opérateur</label>
                <select id="idOperateur" name="idOperateur" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($operateurs as $op) : ?>
                        <option value="<?= $op['id'] ?>" <?= old('idOperateur') == $op['id'] ? 'selected' : '' ?>>
                            <?= esc($op['nom']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
