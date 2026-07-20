# Configuration de SQLite avec CodeIgniter 4

Ce document décrit comment le projet a été configuré pour fonctionner avec **SQLite**,
et comment procéder en cas de modification (schéma, emplacement de la base, etc.).

---

## 1. Prérequis (déjà vérifiés sur cette machine)

| Élément | Requis | Vérification |
|---|---|---|
| PHP >= 8.1 | oui | `php -v` (ici : PHP 8.3.6) |
| Extension `sqlite3` | oui | `php -m \| grep sqlite` |
| Extension `pdo_sqlite` | recommandée | `php -m \| grep sqlite` |
| CLI `sqlite3` | pratique pour créer/inspecter la base | `sqlite3 --version` |

Si une extension manque (sous Ubuntu/Debian) :

```bash
sudo apt install php-sqlite3 sqlite3
```

---

## 2. Étapes réalisées

### 2.1 Configuration de la connexion — `app/Config/Database.php`

Le groupe de connexion `default` (MySQLi à l'origine) a été remplacé par une
configuration SQLite3 :

```php
public array $default = [
    // Chemin relatif => le fichier est cherché dans writable/
    'database'    => 'base.sqlite',
    'DBDriver'    => 'SQLite3',
    'DBPrefix'    => '',
    'DBDebug'     => true,
    'swapPre'     => '',
    'failover'    => [],
    // Active PRAGMA foreign_keys sur chaque connexion
    'foreignKeys' => true,
    'busyTimeout' => 1000,
    'synchronous' => null,
    'dateFormat'  => [
        'date'     => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'time'     => 'H:i:s',
    ],
];
```

**Points importants :**

- `'database' => 'base.sqlite'` : quand le nom ne contient **pas** de séparateur
  de dossier (`/`), CodeIgniter le préfixe automatiquement par `WRITEPATH`
  → le fichier réel est **`writable/base.sqlite`**.
- `'foreignKeys' => true` : indispensable. SQLite **désactive** les clés
  étrangères par défaut ; cette option fait exécuter `PRAGMA foreign_keys = ON`
  à chaque connexion, sinon les `FOREIGN KEY` du schéma ne seraient pas
  appliquées.
- `busyTimeout` : évite les erreurs "database is locked" si deux requêtes
  accèdent au fichier en même temps.

### 2.2 Fichier d'environnement — `.env`

```bash
cp env .env
```

puis dans `.env`, la ligne suivante a été décommentée / modifiée :

```dotenv
CI_ENVIRONMENT = development
```

En mode `development`, les erreurs détaillées et la debug toolbar sont affichées.

### 2.3 Création de la base à partir du schéma — `base.sql`

Le schéma (6 tables : `operateurs`, `prefixes`, `comptes`, `typeMouvement`,
`frais`, `mouvement`) se trouve dans `base.sql` à la racine. La base a été
créée avec :

```bash
sqlite3 writable/base.sqlite < base.sql
```

### 2.4 Vérification de la connexion CodeIgniter

```bash
php spark db:table --show
```

Résultat attendu : le driver `SQLite3`, la base `base.sqlite` et la liste des
6 tables.

### 2.5 `.gitignore`

Un `.gitignore` a été créé pour ne **pas** versionner :

- `.env` (config locale / secrets) ;
- `writable/*.sqlite` (la base est une donnée locale — c'est `base.sql`,
  le schéma, qui fait référence dans git) ;
- les fichiers générés (`writable/cache`, `logs`, `session`, …).

---

## 3. En cas de modification — les scénarios

### 3.1 Modifier le schéma (ajouter une table, une colonne…)

**Règle du projet : `base.sql` est la source de vérité du schéma.**

1. Modifier `base.sql` (ajouter le `CREATE TABLE`, la colonne, etc.).
2. Recréer la base :

   ```bash
   rm writable/base.sqlite
   sqlite3 writable/base.sqlite < base.sql
   ```

   ⚠️ **Cela supprime toutes les données.** En phase de développement c'est
   généralement acceptable. Si les données doivent être conservées, appliquer
   plutôt un `ALTER TABLE` directement :

   ```bash
   sqlite3 writable/base.sqlite "ALTER TABLE comptes ADD COLUMN email TEXT;"
   ```

   … **et** reporter le même changement dans `base.sql` pour que le schéma
   reste synchronisé.

3. Mettre à jour le fichier `tables` (le mémo des colonnes) si vous l'utilisez.

**Limite de SQLite à connaître :** `ALTER TABLE` est restreint (pas de
`DROP COLUMN` avant SQLite 3.35, pas de modification de type ni d'ajout de
contrainte `FOREIGN KEY` après coup). Pour ces cas-là, la seule solution est
de recréer la table (ou toute la base depuis `base.sql`).

### 3.2 Renommer ou déplacer le fichier de base

- Modifier la clé `'database'` dans `app/Config/Database.php`.
- **Nom simple** (`'mabase.sqlite'`) → le fichier sera dans `writable/`.
- **Chemin absolu** (`WRITEPATH . 'db/mabase.sqlite'`) → utilisé tel quel ;
  créer le dossier au préalable et vérifier que PHP a le droit d'écriture
  sur le **dossier** (SQLite crée des fichiers temporaires `-journal` à côté).
- Adapter la ligne `writable/*.sqlite` du `.gitignore` si le fichier sort de
  `writable/`.

### 3.3 Repartir de zéro (reset complet)

```bash
rm writable/base.sqlite
sqlite3 writable/base.sqlite < base.sql
```

### 3.4 Changer de SGBD plus tard (ex. retour à MySQL)

- Remplacer le groupe `default` de `app/Config/Database.php` (des exemples
  commentés pour MySQL/Postgres sont dans le fichier).
- Attention aux différences de dialecte SQL présentes dans `base.sql` :
  `INTEGER PRIMARY KEY AUTOINCREMENT` (SQLite) ↔ `INT AUTO_INCREMENT` (MySQL),
  types `TEXT`/`REAL` ↔ `VARCHAR`/`DECIMAL`, etc. Le fichier devra être adapté.
- Si le code n'utilise que le **Query Builder** et les **Models** de
  CodeIgniter (pas de SQL brut), la migration sera quasi transparente.

### 3.5 Inspecter la base pendant le développement

```bash
# Ouvrir un shell SQL
sqlite3 writable/base.sqlite

# Commandes utiles dans le shell :
.tables                 -- liste des tables
.schema comptes         -- schéma d'une table
.headers on             -- afficher les noms de colonnes
.mode column            -- affichage aligné
SELECT * FROM comptes;

# Ou via CodeIgniter :
php spark db:table comptes
```

---

## 4. Pièges classiques avec SQLite (à retenir)

1. **Clés étrangères non appliquées** → toujours garder
   `'foreignKeys' => true` dans la config (le `PRAGMA foreign_keys = ON` en
   tête de `base.sql` ne vaut que pour la session `sqlite3` qui exécute le
   script, pas pour les connexions de l'application).
2. **"attempt to write a readonly database"** → problème de droits : PHP doit
   pouvoir écrire sur le fichier **et** sur le dossier `writable/`.
3. **"database is locked"** → un autre processus (ex. un shell `sqlite3`
   resté ouvert, un explorateur de BDD) tient un verrou ; fermer l'autre
   connexion. Le `busyTimeout` configuré atténue le problème.
4. **Types laxistes** : SQLite ne force pas les types (on peut insérer du texte
   dans une colonne `REAL`). La validation doit se faire côté application
   (règles de validation des Models CodeIgniter).
5. **`updated_at`** : `DEFAULT CURRENT_TIMESTAMP` ne se met pas à jour tout
   seul lors d'un `UPDATE`. Utiliser les Models CodeIgniter avec
   `$useTimestamps = true` pour que le framework gère `created_at`/`updated_at`.

---

## 5. Récapitulatif des fichiers touchés

| Fichier | Rôle |
|---|---|
| `app/Config/Database.php` | Connexion `default` passée en SQLite3 |
| `.env` | `CI_ENVIRONMENT = development` |
| `base.sql` | Schéma de référence (source de vérité) |
| `writable/base.sqlite` | La base réelle (non versionnée) |
| `.gitignore` | Exclusion de `.env`, de la base et des fichiers générés |
