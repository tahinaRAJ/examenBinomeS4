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
