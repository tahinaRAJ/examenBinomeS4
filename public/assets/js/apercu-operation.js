/**
 * Aperçu en direct des frais d'une opération (dépôt / retrait / transfert).
 *
 * Le formulaire déclare ce qu'il veut simuler via des attributs data- :
 *   <form data-simulation="transfert" data-url="/client/simuler/transfert">
 *   <div id="apercuOperation"></div>
 *
 * Tous les calculs sont faits par le serveur (mêmes méthodes que
 * l'opération réelle) : le JS ne fait qu'afficher le résultat, il ne
 * recalcule jamais un frais de son côté.
 */
(function () {
    'use strict';

    var formulaire = document.querySelector('form[data-simulation]');
    var conteneur  = document.getElementById('apercuOperation');

    if (!formulaire || !conteneur) {
        return;
    }

    var operation = formulaire.getAttribute('data-simulation');
    var url       = formulaire.getAttribute('data-url');
    var minuteur  = null;
    var requete   = 0;

    function formaterAriary(valeur) {
        var arrondi = Math.round(valeur * 100) / 100;
        return arrondi.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' Ar';
    }

    function ligne(libelle, valeur, options) {
        options = options || {};
        var classes = ['apercu-ligne'];
        if (options.total) { classes.push('apercu-ligne-total'); }
        if (options.fort)  { classes.push('apercu-ligne-fort'); }

        var classeValeur = options.couleur ? ' class="' + options.couleur + '"' : '';
        var note = options.note ? '<span class="apercu-note">' + options.note + '</span>' : '';

        return '<div class="' + classes.join(' ') + '">'
             + '<span>' + libelle + note + '</span>'
             + '<span' + classeValeur + '>' + valeur + '</span>'
             + '</div>';
    }

    function afficherMessage(texte, type) {
        conteneur.className = 'apercu apercu-' + (type || 'vide');
        conteneur.innerHTML = '<div class="apercu-message">' + texte + '</div>';
    }

    function afficherResultat(d) {
        var html = '';

        if (operation === 'transfert') {
            html += ligne('Montant envoyé', formaterAriary(d.montant));

            if (d.fraisRetrait > 0) {
                html += ligne('Frais de retrait offert', '+ ' + formaterAriary(d.fraisRetrait), {
                    note: 'tarif ' + d.operateurRecu
                });
            }

            html += ligne('<strong>' + d.nomReceiver + ' reçoit</strong>',
                          '<strong>' + formaterAriary(d.montantRecu) + '</strong>',
                          { fort: true, couleur: 'amount-gain' });

            html += ligne('Frais d\'envoi', '+ ' + formaterAriary(d.frais), {
                note: 'tarif ' + d.operateurSender
            });

            if (d.interOperateurs) {
                html += ligne('Commission inter-opérateurs', '+ ' + formaterAriary(d.commission), {
                    note: d.operateurRecu + ' · ' + d.tauxCommission + ' %'
                });
            }

            html += ligne('<strong>Total débité</strong>',
                          '<strong>' + formaterAriary(d.total) + '</strong>',
                          { total: true });
        } else if (operation === 'retrait') {
            html += ligne('Montant retiré', formaterAriary(d.montant));
            html += ligne('Frais de retrait', '+ ' + formaterAriary(d.frais));
            html += ligne('<strong>Total débité</strong>',
                          '<strong>' + formaterAriary(d.total) + '</strong>',
                          { total: true });
        } else {
            html += ligne('Montant déposé', formaterAriary(d.montant));
            html += ligne('Frais', 'aucun', { couleur: 'muted' });
            html += ligne('<strong>Crédité sur le compte</strong>',
                          '<strong>' + formaterAriary(d.montant) + '</strong>',
                          { total: true, couleur: 'amount-gain' });
        }

        html += ligne('Solde après opération', formaterAriary(d.soldeApres), {
            couleur: d.soldeSuffisant ? '' : 'amount-loss'
        });

        if (!d.soldeSuffisant) {
            html += '<div class="apercu-alerte">Solde insuffisant : il manque '
                  + formaterAriary(d.total - d.solde) + '.</div>';
        }

        conteneur.className = 'apercu apercu-ok' + (d.soldeSuffisant ? '' : ' apercu-insuffisant');
        conteneur.innerHTML = html;
    }

    function simuler() {
        var donnees = new FormData(formulaire);
        var montant = parseFloat(donnees.get('montant'));

        if (!montant || montant <= 0) {
            afficherMessage('Saisissez un montant pour voir le détail des frais.');
            return;
        }

        var numero = ++requete;
        conteneur.classList.add('apercu-chargement');

        fetch(url, { method: 'POST', body: donnees, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                // Ignore les réponses arrivées dans le désordre
                if (numero !== requete) { return; }

                if (d.ok) {
                    afficherResultat(d);
                } else {
                    afficherMessage(d.message || 'Impossible de calculer les frais.', 'attente');
                }
            })
            .catch(function () {
                if (numero !== requete) { return; }
                afficherMessage('Aperçu indisponible — le calcul sera fait à la validation.', 'attente');
            });
    }

    function planifier() {
        clearTimeout(minuteur);
        minuteur = setTimeout(simuler, 350);   // on attend la fin de la frappe
    }

    formulaire.addEventListener('input', planifier);
    formulaire.addEventListener('change', planifier);

    afficherMessage('Saisissez un montant pour voir le détail des frais.');
})();
