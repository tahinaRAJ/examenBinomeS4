# Todo — étapes réalisées (avec durée)

> Développement des fonctionnalités : CRUD préfixes, gestion des tarifs,
> dashboard (gains, historique, comptes clients).
> Durée totale : **~15 minutes**.

## Préparation

- [x] Copie des assets du template Crypto Vault (`docs/css`, `docs/js`)
      vers `public/assets/` — **1 min**
- [x] Layout commun `app/Views/layout/main.php` (sidebar, header, flash,
      thème) + `public/assets/css/app.css` (formulaires, tableaux, badges
      réutilisant les variables CSS du template) — **2 min**

## Models (toute la logique base de données)

- [x] `OperateurModel`, `PrefixeModel` (liste avec opérateur, validation),
      `TypeMouvementModel` (types tarifables) — **1 min**
- [x] `FraisModel` : CRUD + `fraisPour()` (tarif en vigueur à une date),
      `CompteModel` : stats, détail, activation, timestamps,
      `MouvementModel` : stats globales, gains par jour, détails d'un jour,
      mouvements d'un compte, répartition par type — **2 min**

## Controllers + Routes

- [x] `Prefixes`, `Tarifs` (date d'entrée en vigueur optionnelle),
      `Comptes` (CRUD + toggle activation), `Dashboard` (stats + détail
      gains d'un jour) — **2 min**
- [x] `Routes.php` : toutes les routes GET/POST — **1 min**

## Vues

- [x] Préfixes : liste + formulaire d'ajout sur la même page, édition — **1 min**
- [x] Tarifs : grille historisée, ajout avec champ date, édition — **1 min**
- [x] Comptes : liste + stats, détail avec mouvements entrant/sortant,
      formulaire création/édition commun — **2 min**
- [x] Dashboard : cartes de gains/stats, historique des gains par jour
      avec page de détails, répartition par type, liste des comptes — **2 min**

## Vérification

- [x] Données de test (avec changement de tarif au 15/07 pour démontrer le
      frais figé), intégrées dans `base.sql` à la suite du schéma — **1 min**
- [x] Test de toutes les pages (HTTP 200) + test des écritures : ajout
      préfixe, toggle compte, ajout tarif avec date — puis nettoyage des
      écritures de test — **2 min**

## Documentation

- [x] `docs/documentation/` créé, .md existants déplacés dedans,
      `application.md` (architecture, fonctionnalités, routes, scénarios de
      modification) + ce todo — **2 min**

## Filtre par opérateur dans le dashboard

> Les stats du dashboard mélangeaient tous les opérateurs (Telma, Orange,
> Airtel). Ajout d'un sélecteur pour filtrer, ou choisir "Tous les
> opérateurs". Durée : **~10 minutes**.

- [x] `MouvementModel` : méthode `filtrerParOperateur()` (jointures LEFT JOIN
      sur `comptes` en tant qu'émetteur/destinataire) + paramètre optionnel
      `?int $idOperateur` sur `stats()`, `gainsDuJour()`, `gainsParJour()`,
      `detailsDuJour()`, `statsParType()` — **3 min**
- [x] `CompteModel` : même paramètre optionnel sur `listeAvecOperateur()` et
      `stats()` — **1 min**
- [x] `DashboardController` : lecture de `?operateur=` en GET, propagation
      à toutes les méthodes des Models, sur `index()` et `gains()` — **2 min**
- [x] Vues : sélecteur `<select>` (soumission auto au changement) dans
      `dashboard/index.php`, propagation du filtre dans les liens vers
      `dashboard/gains/{jour}`, badge de rappel du filtre actif sur cette
      page — **2 min**
- [x] Données de test réparties sur plusieurs opérateurs (au lieu de tout
      Telma) pour pouvoir démontrer le filtre, + tests des 4 cas (tous /
      Telma / Orange / Airtel) sur le serveur local — **2 min**

## Documentation détaillée du code

- [x] `partie1-cote-operateur.md` : explication complète, fichier par
      fichier et méthode par méthode, de tous les Models/Controllers/Vues/
      Routes déjà écrits, pour pouvoir relire et modifier le code — **10 min**

## Partie client (Tahina)

> Interface client par-dessus la base existante : authentification, dépôt,
> retrait, transfert, consultation du profil et de l'historique.

### Models (toute la logique base de données)

- [x] `CompteModel` : authentification (`verifierIdentifiants()`), fonctions
      d'accès aux informations (`getNom()`, `getSolde()`, `getNum()`)
- [x] `MouvementModel` : fonctions d'opération (`deposer()`, `retirer()`,
      `transferer()`)

### Controllers + Routes

- [x] Contrôleur d'authentification du client (connexion, déconnexion)
- [x] Contrôleur client (profil, historique des mouvements)
- [x] Controleur mouvement (depot, retrait, transfert)
- [x] `Routes.php` : routes de connexion + routes protégées de l'espace
      client

### Vues

- [x] `layout/client.php` : layout dédié à l'espace client (navigation
      Profil / Historique / Dépôt / Retrait / Transfert, bouton de
      déconnexion en POST, identité lue en session, gestion des flash
      `success` / `erreur` / `error`) — **4 min**
- [x] Connexion : page autonome (sans layout) reprenant
      `templatemo-crypto-login.css`, champ numéro de compte conservé via
      `old()` — **3 min**
- [x] Profil : cartes solde / numéro / statut / date d'ouverture, tableau
      des informations et accès rapide aux opérations — **3 min**
- [x] Opérations : formulaires de dépôt / retrait / transfert, chacun avec
      le solde courant et le rappel de sa règle de tarification — **5 min**
- [x] Historique : tableau des mouvements (sens entrant/sortant, montant
      coloré, frais figés) + 4 totaux calculés en PHP dans la vue, sans
      requête supplémentaire — **4 min**

### Vérification

- [x] Test des 3 opérations et de leurs cas d'erreur sur le serveur local :
      dépôt 50 000 (frais 0), retrait 20 000 (frais 500), transfert 5 000
      (frais **150** = tarif en vigueur depuis le 15/07, ce qui valide la
      grille datée), montant nul/négatif, solde insuffisant, destinataire
      introuvable, transfert vers soi-même, accès sans connexion sur les
      5 pages, connexion d'un compte inactif, déconnexion. Base restaurée
      depuis `base.sql` après les tests — **6 min**

## Documentation détaillée du code (partie 2)

- [x] `partie2-cote-client.md` : explication complète de l'espace client —
      la session, les transactions SQL, le retour
      `['success' => …, 'message' => …]`, les 3 opérations, les vues et le
      layout client, plus un guide pour ajouter un mot de passe ou une
      nouvelle opération — **9 min**

## Réorganisation des routes et navigation

- [x] Correction de la régression du passage de `/` sur le login : la route
      `GET login` avait disparu alors que les controllers y redirigent à
      6 endroits (404 sur tout l'espace client). Route rétablie (`/` **et**
      `/login` affichent la connexion) + 4 vues et 1 redirection repointées
      vers `dashboard` — le lien « Dashboard » de la sidebar et le
      **formulaire de filtre du dashboard** renvoyaient au login — **7 min**
- [x] Bouton « Quitter l'espace opérateur » dans la sidebar de
      `layout/main.php` (lien `<a>` vers `/login`, pas un POST : l'espace
      opérateur n'a pas de session à détruire). Présent automatiquement sur
      les 7 pages opérateur — **3 min**
- [x] Mise à jour de `partie2-cote-client.md` : nouvelle organisation des
      routes, explication de la régression et de la règle à suivre quand on
      change une route, navigation entre les deux espaces — **4 min**

## Tarifs propres à chaque opérateur

> Avant, la grille `frais` était commune à tous les opérateurs. Désormais
> chaque opérateur fixe ses propres tarifs.

- [x] `base.sql` : colonne `idOperateur` dans `frais` + clé étrangère vers
      `operateurs`, et grille de test pour les 3 opérateurs (19 tranches :
      Telma plus cher sur l'envoi, Orange sur le retrait, Airtel moins cher)
      — **6 min**
- [x] `FraisModel` : `fraisPour()` prend un `$idOperateur` **obligatoire**
      (3ᵉ argument, sans valeur par défaut, pour forcer tous les appelants à
      le préciser) ; `listeAvecType()` joint `operateurs` et accepte un
      filtre ; `idOperateur` ajouté aux champs et à la validation — **5 min**
- [x] `MouvementModel` : les 2 appels passent l'opérateur du **payeur** —
      le titulaire pour `retirer()`, l'**émetteur** pour `transferer()`
      — **3 min**
- [x] Controller `Tarifs` + vues : filtre par opérateur sur la liste (même
      principe que le dashboard), colonne « Opérateur » dans le tableau,
      sélecteur dans les formulaires d'ajout et de modification ; le filtre
      actif pré-remplit l'opérateur du formulaire d'ajout — **7 min**
- [x] Vérification : retrait de 5 000 Ar → 200 (Telma) / 250 (Orange) /
      150 (Airtel) ; envoi de 6 000 Ar entre opérateurs différents → tarif de
      l'émetteur (150 / 80 / 120) ; le tarif daté Telma du 15/07 fonctionne
      toujours par-dessus ; CRUD complet des tarifs, filtre exact (19/7/6/6),
      dashboard cohérent, 0 violation de clé étrangère — **8 min**
- [x] Documentation : `frais-fige.md` réécrit autour des 3 mécanismes
      (opérateur / date / frais figé), sections `FraisModel` et `Tarifs` de
      `partie1`, sections `retirer()` et `transferer()` de `partie2`,
      mémo `tables` — **9 min**

## Échanges entre opérateurs (partie 3)

> Voir `partie3-inter-operateurs.md` pour l'explication complète du code.

- [x] `base.sql` : `operateurs.pourcentageCommission` + `mouvement.commission`
      (deux colonnes séparées car frais et commission vont à deux opérateurs
      différents), et **recalcul complet des 11 mouvements de test** avec le
      frais de la grille de leur émetteur et la commission du destinataire
      — **8 min**
- [x] **Préfixes fonctionnels** : `PrefixeModel::operateurPourNumero()` (tri
      par longueur décroissante pour gérer les préfixes qui se chevauchent) et
      `numeroAppartientA()`. Utilisés à la création/modification d'un compte :
      un numéro 032… ne peut plus être rattaché à Telma — **6 min**
- [x] **Commission** : `commissionPour()` dans `MouvementModel` (0 si transfert
      interne, sinon % de l'opérateur d'arrivée), `transferer()` débite
      `montant + frais + commission` et crédite le montant exact ; message
      d'erreur et de succès enrichis — **7 min**
- [x] **Écran Opérateurs** (`/operateurs`) : configuration du % par opérateur,
      avec le nombre de préfixes / tarifs / comptes et un ⚠ si la grille
      tarifaire est vide — **6 min**
- [x] **Point 3 — séparation des gains** : `gainsDetailles()` avec des
      `SUM(CASE WHEN…)` (une seule lecture de table pour trois totaux) ;
      section dédiée sur le dashboard, affichée uniquement quand un opérateur
      précis est sélectionné — **7 min**
- [x] **Point 4 — compensation** : `situationCompensation()` et
      `detailsCompensation()`, controller `Compensation`, pages liste +
      détail. Le montant dû = `montant + commission` (jamais les frais)
      — **9 min**
- [x] Routes + liens de navigation (Opérateurs, Compensation) — **2 min**
- [x] Vérification : les 3 cas de préfixe, transfert inter-opérateurs
      (émetteur −10 300 / destinataire +10 000 pile, frais et commission
      stockés séparément), transfert interne (commission 0), gains détaillés
      et compensation croisés avec le SQL, intégrité et logs — **9 min**
- [x] Documentation `partie3-inter-operateurs.md` + mémo `tables` — **10 min**

## Interface : aperçu en direct des frais (AJAX) et design

- [x] **Correction du bug « frais de retrait inclus »** : l'appel à
      `fraisPour()` ne passait que 2 arguments sur 3 → `ArgumentCountError`,
      erreur 500 dès que la case était cochée. Au passage `$fraisRetrait`
      n'était jamais calculé, le type demandé était `'Envoi'` au lieu de
      `'Retrait'`, et le destinataire était crédité de `$montant` alors que le
      mouvement enregistrait `$montantRecu`. Corrigé selon la règle : frais de
      retrait calculé avec la grille de l'**opérateur du destinataire**, ajouté
      au montant reçu — **8 min**
- [x] **Simulations dans le Model** : `simulerTransfert()`, `simulerRetrait()`
      et `simulerDepot()` — même calcul que les opérations réelles mais sans
      aucune écriture. La règle « pas de SQL hors des Models » est respectée :
      le JS n'effectue aucun calcul de frais — **7 min**
- [x] **Endpoint JSON** `POST client/simuler/(transfert|retrait|depot)` +
      `POST tarifs/simuler`, protégés par la session — **4 min**
- [x] **`apercu-operation.js`** : aperçu en direct sous les formulaires client
      (anti-rebond de 350 ms, réponses hors-ordre ignorées, dégradation propre
      si le réseau échoue). Affiche montant, frais de retrait offert, ce que
      reçoit le destinataire, frais d'envoi, commission (avec taux et
      opérateur), total débité et solde après opération — **9 min**
- [x] **`simulateur-tarif.js`** : sur l'écran Tarifs, vérifier le frais
      appliqué pour un opérateur/type/montant sans créer de mouvement, avec
      alerte si aucune tranche ne couvre le montant — **5 min**
- [x] Section `scripts` ajoutée aux deux layouts pour injecter le JS par page
      — **1 min**
- [x] **Design** : styles de l'aperçu (lignes, total, alerte, chiffres alignés
      en `tabular-nums`), case à cocher lisible (`.form-check`), blocs
      d'information, focus visible pour l'accessibilité, et **états vides**
      des 11 tableaux remplacés par un titre + une aide au lieu d'une ligne
      grise — **10 min**
- [x] Vérification : l'aperçu annonce **exactement** ce que l'opération
      exécute (3 cas testés, total débité et montant reçu identiques au
      centime), 16 pages en 200, endpoints conformes, syntaxe PHP et JS
      valides — **7 min**

## Transfert vers plusieurs destinataires

- [x] Formulaire dynamique : ajout et retrait de plusieurs numéros
- [x] Répartition du montant total à parts égales entre les destinataires
- [x] Calcul des frais et commissions sur chaque part, puis addition des totaux
- [x] Aperçu AJAX détaillé : part individuelle, montant reçu par numéro, frais
      d'envoi totaux, commissions et total débité
- [x] Transaction atomique : un numéro invalide annule tout le transfert
- [x] Contrôle des numéros vides, dupliqués, inactifs et du compte émetteur



## Aléa

- [] créer la table epargne (id , idcompte, pourcentage, montant)
- [] créer un model et un controller (avec la fonction insertEpargne)
- [] modifier la fonction transferMultiple :
            - apres le transfert , on retire un pourcentage de l'argent recu
            - on l'additionne au montant de l'épargne
