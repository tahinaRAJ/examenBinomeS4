/**
 * Simulateur de tarif (écran Tarifs, côté opérateur).
 *
 * Interroge le serveur pour connaître le frais qui s'appliquerait à un
 * opérateur / type / montant donnés. Le calcul reste côté serveur : c'est
 * la même méthode FraisModel::fraisPour() que les opérations réelles.
 */
(function () {
    'use strict';

    var formulaire = document.getElementById('simulateurTarif');
    var resultat   = document.getElementById('resultatSimulateur');

    if (!formulaire || !resultat) {
        return;
    }

    var url      = formulaire.getAttribute('data-url');
    var minuteur = null;
    var requete  = 0;

    function formaterAriary(valeur) {
        var arrondi = Math.round(valeur * 100) / 100;
        return arrondi.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' Ar';
    }

    function ligne(libelle, valeur, options) {
        options = options || {};
        var classes = 'apercu-ligne' + (options.total ? ' apercu-ligne-total' : '');
        var couleur = options.couleur ? ' class="' + options.couleur + '"' : '';

        return '<div class="' + classes + '"><span>' + libelle + '</span>'
             + '<span' + couleur + '>' + valeur + '</span></div>';
    }

    function afficherMessage(texte) {
        resultat.className = 'apercu';
        resultat.innerHTML = '<div class="apercu-message">' + texte + '</div>';
    }

    function simuler() {
        var montant = parseFloat(formulaire.querySelector('[name="montant"]').value);

        if (!montant || montant <= 0) {
            afficherMessage('Saisissez un montant pour voir le frais appliqué.');
            return;
        }

        var donnees = new FormData(formulaire);
        var numero  = ++requete;
        resultat.classList.add('apercu-chargement');

        fetch(url, { method: 'POST', body: donnees, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (numero !== requete) { return; }

                if (!d.ok) {
                    afficherMessage(d.message || 'Simulation impossible.');
                    return;
                }

                var html = ligne(d.type + ' — ' + d.operateur, formaterAriary(d.montant));
                html += ligne('Frais appliqué', formaterAriary(d.frais), {
                    couleur: d.aucuneGrille ? 'amount-loss' : 'amount-gain'
                });
                html += ligne('<strong>Total pour le client</strong>',
                              '<strong>' + formaterAriary(d.total) + '</strong>',
                              { total: true });

                if (d.aucuneGrille) {
                    html += '<div class="apercu-alerte">Aucune tranche ne couvre ce montant '
                          + 'pour cet opérateur : le frais serait de 0 Ar.</div>';
                }

                resultat.className = 'apercu apercu-ok';
                resultat.innerHTML = html;
            })
            .catch(function () {
                if (numero !== requete) { return; }
                afficherMessage('Simulation indisponible.');
            });
    }

    function planifier() {
        clearTimeout(minuteur);
        minuteur = setTimeout(simuler, 350);
    }

    formulaire.addEventListener('input', planifier);
    formulaire.addEventListener('change', planifier);

    afficherMessage('Saisissez un montant pour voir le frais appliqué.');
})();
