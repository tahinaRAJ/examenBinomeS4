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
                <label for="numeroReceiver">Numéro du destinataire</label>
                <input type="text" id="numeroReceiver" name="numeroReceiver"
                       placeholder="Ex. 0344455667" required autofocus
                       autocomplete="off" inputmode="numeric">
                <p class="form-aide">L'opérateur est reconnu automatiquement d'après le préfixe.</p>
            </div>

            <div class="form-group">
                <label for="montant">Montant à envoyer (Ar)</label>
                <input type="number" id="montant" name="montant" min="1" step="1"
                       placeholder="Ex. 10000" required inputmode="numeric">
            </div>

            <div class="form-group">
                <label class="form-check" for="retraitInclus">
                    <input type="checkbox" id="retraitInclus" name="retraitInclus" value="1">
                    <span class="form-check-texte">
                        Inclure les frais de retrait
                        <span class="form-check-aide">
                            Vous payez en plus le frais de retrait du destinataire : il recevra
                            de quoi retirer la totalité du montant.
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
            Le <strong>frais d'envoi</strong> dépend de votre opérateur et du montant.<br>
            Si le destinataire est chez un <strong>autre opérateur</strong>, une
            <strong>commission</strong> en pourcentage s'ajoute — elle revient à l'opérateur
            qui reçoit l'argent.<br>
            Le destinataire reçoit toujours le montant exact que vous avez saisi.
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/apercu-operation.js') ?>"></script>
<?= $this->endSection() ?>
