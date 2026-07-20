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

    function echapper(texte) {
        return String(texte).replace(/[&<>'"]/g, function (caractere) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[caractere];
        });
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
            html += ligne('Montant total à répartir', formaterAriary(d.montant));
            html += ligne('Nombre de destinataires', String(d.nombreDestinataires));
            html += ligne('<strong>Part par destinataire</strong>',
                          '<strong>' + formaterAriary(d.montantParDestinataire) + '</strong>',
                          { fort: true });

            (d.destinataires || []).forEach(function (destination) {
                var identite = echapper(destination.nom) + ' (' + echapper(destination.numero) + ') reçoit';
                html += ligne(identite, formaterAriary(destination.montantRecu), {
                    note: echapper(destination.operateur), couleur: 'amount-gain'
                });
            });

            if (d.fraisRetrait > 0) {
                html += ligne('Total des frais de retrait offerts', '+ ' + formaterAriary(d.fraisRetrait));
            }

            html += ligne('Total des frais d\'envoi', '+ ' + formaterAriary(d.frais), {
                note: 'calculés séparément sur chaque part · tarif ' + echapper(d.operateurSender)
            });

            if (d.commission > 0) {
                html += ligne('Total des commissions inter-opérateurs', '+ ' + formaterAriary(d.commission));
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

    var boutonAjouter = document.getElementById('ajouterDestinataire');
    var listeDestinataires = document.getElementById('listeDestinataires');

    function actualiserBoutonsSuppression() {
        if (!listeDestinataires) { return; }
        var lignes = listeDestinataires.querySelectorAll('.destinataire-ligne');
        lignes.forEach(function (ligne) {
            var bouton = ligne.querySelector('.supprimer-destinataire');
            if (bouton) { bouton.disabled = lignes.length <= 1; }
        });
    }

    if (boutonAjouter && listeDestinataires) {
        boutonAjouter.addEventListener('click', function () {
            var numeroLigne = listeDestinataires.children.length + 1;
            var ligne = document.createElement('div');
            ligne.className = 'destinataire-ligne';
            ligne.innerHTML = '<input type="text" name="numerosReceiver[]" required '
                + 'placeholder="Ex. 0344455667" autocomplete="off" inputmode="numeric" '
                + 'aria-label="Numéro du destinataire ' + numeroLigne + '">'
                + '<button type="button" class="btn btn-sm btn-danger supprimer-destinataire" '
                + 'aria-label="Supprimer ce destinataire">Retirer</button>';
            listeDestinataires.appendChild(ligne);
            ligne.querySelector('input').focus();
            actualiserBoutonsSuppression();
            planifier();
        });

        listeDestinataires.addEventListener('click', function (evenement) {
            var bouton = evenement.target.closest('.supprimer-destinataire');
            if (!bouton) { return; }
            bouton.closest('.destinataire-ligne').remove();
            actualiserBoutonsSuppression();
            planifier();
        });
    }

    actualiserBoutonsSuppression();
    afficherMessage('Saisissez un montant pour voir le détail des frais.');
})();
