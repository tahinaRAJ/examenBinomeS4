# Application MobileMoney — architecture et fonctionnalités

> Pour une explication détaillée, fichier par fichier et méthode par
> méthode, de tout le code (Models, Controllers, Vues, Routes, filtre par
> opérateur du dashboard...), voir **[partie1-cote-operateur.md](partie1-cote-operateur.md)**.

## Règle d'or du projet

**Aucun accès à la base de données en dehors des Models.** Les controllers ne
font qu'orchestrer : ils appellent les Models, préparent les données et
rendent les vues. Toute requête (lecture, écriture, statistique) vit dans un
Model. Si une nouvelle requête est nécessaire, on ajoute une **méthode au
Model concerné**, jamais un `db_connect()` dans un controller ou une vue.

## Structure

```
app/
├── Config/Routes.php          # toutes les routes
├── Controllers/
│   ├── Dashboard.php          # stats, gains, historique
│   ├── Prefixes.php           # CRUD des préfixes
│   ├── Tarifs.php             # gestion de la grille tarifaire
│   └── Comptes.php            # comptes clients
├── Models/
│   ├── OperateurModel.php
│   ├── PrefixeModel.php       # + listeAvecOperateur()
│   ├── TypeMouvementModel.php # + typesTarifables()
│   ├── FraisModel.php         # + listeAvecType(), fraisPour(type, montant, date)
│   ├── CompteModel.php        # + stats(), detailAvecOperateur(), changerActivation()
│   └── MouvementModel.php     # + stats(), gainsParJour(), detailsDuJour(), mouvementsDuCompte()
└── Views/
    ├── layout/main.php        # layout commun (sidebar, header, flash)
    ├── dashboard/  index.php, gains.php
    ├── prefixes/   index.php, edit.php
    ├── tarifs/     index.php, edit.php
    └── comptes/    index.php, show.php, form.php
```

Le design vient du template **TemplateMo 609 Crypto Vault** (fichiers de
référence dans `docs/`), copié dans `public/assets/`. Les compléments
(formulaires, badges, tableaux applicatifs) sont dans
`public/assets/css/app.css`.

## Les vues et le layout

Chaque vue étend le layout :

```php
<?= $this->extend('layout/main') ?>
<?= $this->section('title') ?>Titre de la page<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Sous-titre<?= $this->endSection() ?>
<?= $this->section('content') ?> ... <?= $this->endSection() ?>
```

Le layout gère la sidebar (lien actif via `url_is()`), le thème clair/sombre
et l'affichage des messages flash `success` / `error`.

## Fonctionnalités

### 1. CRUD des préfixes (`/prefixes`)

- Liste des préfixes avec leur opérateur + formulaire d'ajout sur la même page.
- Modification sur `/prefixes/{id}/edit`, suppression avec confirmation.
- Validation dans `PrefixeModel` (`$validationRules`) — le controller se
  contente de relayer `$model->errors()` vers la vue en flashdata.

### 2. Tarifs (`/tarifs`)

- Grille tarifaire par type d'opération **tarifable** (tranche min/max → frais).
- À l'ajout, on peut **choisir la date d'entrée en vigueur** (`dateFrais`,
  champ `datetime-local`) ; vide = maintenant (DEFAULT de la colonne).
- Modifier / supprimer sont possibles, mais l'écran rappelle la bonne
  pratique : pour un changement de tarif, **ajouter une nouvelle ligne** à la
  date du changement plutôt que modifier l'ancienne, afin de conserver
  l'historique de la grille (voir `frais-fige.md`).
- `FraisModel::fraisPour($idType, $montant, $date)` donne le tarif en vigueur
  à une date : la ligne la plus récente dont `dateFrais <= date`.

### 3. Dashboard (`/`)

- **Situation des gains** : gains totaux (= `SUM(mouvement.frais)`, les frais
  figés), gains du jour, nombre de mouvements, volume échangé.
- **Historique des gains par jour** avec lien « Détails » →
  `/dashboard/gains/{AAAA-MM-JJ}` : chaque mouvement du jour avec son frais
  perçu.
- **Répartition par type d'opération** (nb, gains).
- **Comptes clients** : stats (total, actifs, désactivés, solde cumulé) et
  liste avec accès aux détails / modification.

### 4. Comptes clients (`/comptes`)

- Liste + stats, création (`/comptes/create`), modification
  (`/comptes/{id}/edit`), détail (`/comptes/{id}`) avec l'historique des
  mouvements du compte (entrant/sortant).
- **Activation** : bouton Activer/Désactiver (colonne `estActif`) — on ne
  supprime pas un compte (il est référencé par les mouvements), on le
  désactive.

## Routes

| Méthode | URL | Controller |
|---|---|---|
| GET | `/` | Dashboard::index |
| GET | `/dashboard/gains/{jour}` | Dashboard::gains |
| GET / POST | `/prefixes` | Prefixes::index / store |
| GET | `/prefixes/{id}/edit` | Prefixes::edit |
| POST | `/prefixes/{id}` | Prefixes::update |
| POST | `/prefixes/{id}/delete` | Prefixes::delete |
| GET / POST | `/tarifs` | Tarifs::index / store |
| GET | `/tarifs/{id}/edit` | Tarifs::edit |
| POST | `/tarifs/{id}` | Tarifs::update |
| POST | `/tarifs/{id}/delete` | Tarifs::delete |
| GET / POST | `/comptes` | Comptes::index / store |
| GET | `/comptes/create` | Comptes::create |
| GET | `/comptes/{id}` | Comptes::show |
| GET | `/comptes/{id}/edit` | Comptes::edit |
| POST | `/comptes/{id}` | Comptes::update |
| POST | `/comptes/{id}/toggle` | Comptes::toggle |

## Données de test

`base.sql` contient **le schéma ET les données de test** : tout se charge
d'un seul coup. Les données : 3 opérateurs, 5 préfixes, 3 types de
mouvement, la grille tarifaire **avec un changement de tarif au 15/07**
(l'envoi 0–10000 passe de 100 à 150 Ar — les mouvements antérieurs gardent
100, preuve du frais figé), 6 comptes et 11 mouvements répartis sur
plusieurs jours.

```bash
# Reset complet (schéma + données) :
rm writable/base.sqlite
sqlite3 writable/base.sqlite < base.sql
```

## Lancer l'application

```bash
php spark serve        # puis http://localhost:8080
```

⚠️ Le port doit correspondre au `baseURL` de `app/Config/App.php`
(`http://localhost:8080/`). Si 8080 est occupé, `spark serve` bascule
silencieusement sur 8081/8082 : la page s'affiche alors **sans style**, car
le CSS et le JS sont générés vers 8080. Deux solutions :

- libérer 8080 (`pkill -f "spark serve"`) puis relancer ;
- ou servir sur un autre port **et** changer `baseURL` en conséquence
  (`php spark serve --port 9000` + `baseURL = 'http://localhost:9000/'`).

## Police hébergée localement

La police *Instrument Sans* est servie depuis `public/assets/fonts/`
(fichiers `.woff2`) via `public/assets/css/fonts.css`, au lieu de Google
Fonts.

**Pourquoi :** le `<link>` vers `fonts.googleapis.com` est bloquant pour
l'affichage. Le navigateur attendait le CSS distant (~1 s) puis le fichier
de police (~0,6 s) avant de peindre la page, soit ~1,6 s d'écran vide —
alors que la page HTML elle-même est générée en 13 ms. En local, ces
fichiers répondent en moins d'1 ms, et l'application fonctionne **sans
connexion internet**.

Pour changer de police : télécharger les `.woff2` dans
`public/assets/fonts/` et adapter les `@font-face` de
`public/assets/css/fonts.css` (les `src:` pointent en `../fonts/`).

## En cas de modification

- **Nouvelle requête BDD** → nouvelle méthode dans le Model concerné.
- **Nouvelle page** → route dans `Routes.php` + méthode de controller +
  vue qui étend `layout/main` + lien dans la sidebar du layout.
- **Nouvelle colonne** → modifier `base.sql` (source de vérité), recréer la
  base (ou `ALTER TABLE`, voir `sqlite-setup.md`), ajouter le champ dans
  `$allowedFields` du Model et dans les règles de validation si besoin.
- **Nouveau type de mouvement** → simple `INSERT` dans `typeMouvement` ;
  s'il est tarifable (`estTarifable = 1`), il apparaît automatiquement dans
  l'écran des tarifs.
