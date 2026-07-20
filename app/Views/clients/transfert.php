<?= $this->extend('layout/client') ?>

<?= $this->section('title') ?>Transfert<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Envoyer de l'argent depuis le compte <?= esc($client['numero']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="app-grid-2">
    <div class="app-card">
        <h2>Effectuer un transfert</h2>

        <form action="<?= site_url('client/transfert') ?>" method="post"
              data-simulation="transfert"
              data-url="<?= site_url('client/simuler/transfert') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="montant">Montant total à répartir (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="0.01"
                       placeholder="Ex. 30000" required autofocus inputmode="decimal">
                <p class="form-aide">Ce montant sera divisé à parts égales entre tous les destinataires.</p>
            </div>

            <div class="form-group">
                <div class="destinataires-entete">
                    <label>Numéros des destinataires</label>
                    <button type="button" class="btn btn-sm" id="ajouterDestinataire">+ Ajouter un numéro</button>
                </div>
                <div id="listeDestinataires" class="destinataires-liste">
                    <div class="destinataire-ligne">
                        <input type="text" name="numerosReceiver[]"
                               placeholder="Ex. 0344455667" required
                               autocomplete="off" inputmode="numeric"
                               aria-label="Numéro du destinataire 1">
                    </div>
                </div>
                <p class="form-aide">Chaque numéro doit correspondre à un compte existant et ne peut apparaître qu'une fois.</p>
            </div>

            <div class="form-group">
                <label class="form-check" for="retraitInclus">
                    <input type="checkbox" id="retraitInclus" name="retraitInclus" value="1">
                    <span class="form-check-texte">
                        Inclure les frais de retrait
                        <span class="form-check-aide">
                            Vous payez en plus le frais de retrait de chaque destinataire : chacun
                            recevra de quoi retirer entièrement sa part.
                        </span>
                    </span>
                </label>
            </div>

            <!-- Aperçu en direct : rempli par apercu-operation.js -->
            <div id="apercuOperation" class="apercu"></div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Valider le transfert</button>
                <a class="btn" href="<?= site_url('profil') ?>">Annuler</a>
            </div>
        </form>
    </div>

    <div class="app-card">
        <h2>Votre solde</h2>
        <div class="stat-card primary" style="margin-bottom: 16px;">
            <div class="stat-label">Solde actuel</div>
            <div class="stat-value"><?= number_format((float) $client['solde'], 0, ',', ' ') ?> Ar</div>
        </div>

        <div class="info-bloc">
            <strong>Comment sont calculés les frais ?</strong><br>
            Le montant total est d'abord <strong>divisé par le nombre de destinataires</strong>.<br>
            Le <strong>frais d'envoi</strong> est ensuite calculé sur chaque part, jamais sur
            le montant total, puis tous les frais sont additionnés.<br>
            Une commission peut aussi s'ajouter séparément pour chaque transfert vers un autre opérateur.
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/apercu-operation.js') ?>"></script>
<?= $this->endSection() ?>
