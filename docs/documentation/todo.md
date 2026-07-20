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
