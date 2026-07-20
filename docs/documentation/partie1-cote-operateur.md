# Partie 1 — Côté opérateur : explication complète du code

Ce document explique **tout** le code écrit jusqu'ici (Models, Controllers,
Vues, Routes), fichier par fichier, pour que vous puissiez le relire, le
comprendre et le modifier vous-même. C'est la « Partie 1 » car ce qui existe
aujourd'hui est l'**interface d'administration de l'opérateur** (back-office) :
gérer les préfixes, les tarifs, consulter les gains et gérer les comptes
clients. Une éventuelle Partie 2 (interface client, authentification, etc.)
viendrait s'ajouter par-dessus cette base sans la remettre en cause.

Si vous ne connaissez pas encore CodeIgniter 4, commencez par la section 0.

---

## 0. Les bases de CodeIgniter 4 utilisées ici

CodeIgniter suit le pattern **MVC** (Modèle - Vue - Contrôleur) :

- **Model** (`app/Models/`) : la SEULE couche qui parle à la base de
  données. Toutes les requêtes SQL passent par ici (c'est la règle du projet).
- **Controller** (`app/Controllers/`) : reçoit la requête HTTP (GET/POST),
  appelle les Models pour lire/écrire des données, choisit une Vue à afficher.
- **Vue** (`app/Views/`) : du HTML + un peu de PHP pour afficher les données
  reçues du Controller. Aucune requête SQL n'y est jamais faite.

Quand une URL est visitée, voici le trajet :

```
Navigateur → Routes.php (quelle URL va vers quel Controller ?)
           → Controller (récupère les données via un Model)
           → Model (fait la requête SQL, retourne un tableau PHP)
           → Controller (passe les données à une Vue)
           → Vue (affiche le HTML avec les données)
           → Navigateur
```

### Fonctions/notions CodeIgniter qui reviennent partout

| Élément | Rôle |
|---|---|
| `model(MaClasseModel::class)` | Crée (ou récupère) une instance du Model. Équivalent à `new MaClasseModel()` mais recommandé par CI4. |
| `$this->request->getPost('champ')` | Récupère la valeur d'un champ envoyé en POST (formulaire). |
| `$this->request->getPost(['a', 'b'])` | Récupère plusieurs champs d'un coup, sous forme de tableau associatif `['a' => ..., 'b' => ...]`. |
| `$this->request->getGet('champ')` | Récupère un paramètre d'URL `?champ=valeur` (GET). |
| `view('dossier/fichier', $donnees)` | Rend une vue avec les données passées en tableau associatif ; chaque clé devient une variable disponible dans la vue (`'jour' => $x` → `$jour` dans la vue). |
| `redirect()->to('url')` | Redirige le navigateur vers une autre URL. |
| `->with('success', '...')` | Ajoute un message flash (visible une seule fois, à la prochaine page). |
| `->withInput()` | Renvoie les valeurs du formulaire pour pouvoir les réafficher avec `old()` après une erreur. |
| `esc($valeur)` | **Toujours utiliser dans les vues** pour afficher une donnée utilisateur : échappe le HTML et empêche les failles XSS. |
| `old('champ', $defaut)` | Dans un formulaire, réaffiche la valeur précédemment saisie (après une erreur de validation), ou `$defaut` sinon. |
| `csrf_field()` | Génère un champ caché obligatoire dans **tout formulaire POST** (protection CSRF, activée par défaut dans CodeIgniter). Sans lui, le formulaire est rejeté. |
| `site_url('chemin')` | Construit une URL absolue vers une route de l'application. |

### Le Query Builder (dans les Models)

CodeIgniter fournit un « constructeur de requêtes » qui évite d'écrire du SQL
à la main (et protège automatiquement contre les injections SQL) :

```php
$this->select('comptes.*, operateurs.nom AS nomOperateur')
    ->join('operateurs', 'operateurs.id = comptes.idOperateur')
    ->where('comptes.idOperateur', $id)
    ->orderBy('comptes.nom')
    ->findAll();
```

équivaut à :

```sql
SELECT comptes.*, operateurs.nom AS nomOperateur
FROM comptes
JOIN operateurs ON operateurs.id = comptes.idOperateur
WHERE comptes.idOperateur = ?
ORDER BY comptes.nom
```

`findAll()` retourne toutes les lignes (tableau de tableaux). `first()` /
`get()->getRowArray()` retourne une seule ligne. `get()->getResultArray()`
retourne toutes les lignes (comme `findAll()` mais utilisable après un
`select()` avec agrégats/`groupBy`).

---

## 1. Le schéma de données (rappel)

6 tables, décrites en détail dans `base.sql` :

- **operateurs** — Telma, Orange, Airtel… (le référentiel des opérateurs).
- **prefixes** — quel préfixe téléphonique (034, 032…) appartient à quel opérateur.
- **comptes** — les comptes clients (numéro, nom, solde, `estActif`,
  `idOperateur` = à quel opérateur appartient le numéro du client).
- **typeMouvement** — Dépôt / Retrait / Envoi, avec `estTarifable` (soumis à
  frais ou non).
- **frais** — la grille tarifaire, **propre à chaque opérateur**
  (`idOperateur`) et historisée par `dateFrais` (voir `frais-fige.md`).
- **mouvement** — chaque opération, avec `frais` = le frais **figé** au
  moment de l'opération (jamais recalculé).

**Point clé pour comprendre le filtre du dashboard** : un mouvement n'a pas
de colonne `idOperateur` directe. Son « opérateur » se déduit des comptes
`idSender` / `idReceiver` qu'il relie. Un mouvement entre deux clients
d'opérateurs différents (ex. Telma → Orange) concerne donc **les deux
opérateurs à la fois**.

---

## 2. Les Models (`app/Models/`)

Rappel de la règle du projet : **un controller n'exécute jamais de SQL**. Dès
qu'une nouvelle donnée est nécessaire, on ajoute une méthode au Model
concerné plutôt que d'écrire une requête ailleurs.

### 2.1 `OperateurModel.php`

Le plus simple : juste la configuration de base (table, clé primaire, champs
autorisés à l'écriture). Utilisé directement via ses méthodes héritées
(`find()`, `findAll()`, `orderBy()->findAll()`) sans méthode personnalisée.

### 2.2 `PrefixeModel.php`

```php
protected $allowedFields = ['prefixe', 'idOperateur'];
```
`$allowedFields` liste les colonnes qu'on autorise à remplir via
`insert()`/`update()` avec un tableau (protection contre l'injection de
champs non voulus, ex. quelqu'un qui enverrait `id` dans le formulaire).

```php
protected $validationRules = [
    'prefixe'     => 'required|min_length[2]|max_length[5]',
    'idOperateur' => 'required|is_natural_no_zero',
];
```
Ces règles sont **vérifiées automatiquement** par `insert()`/`update()` :
si elles échouent, l'insertion/modification est refusée et
`$model->errors()` contient les messages (définis juste après dans
`$validationMessages`).

```php
public function listeAvecOperateur(): array
{
    return $this->select('prefixes.*, operateurs.nom AS nomOperateur')
        ->join('operateurs', 'operateurs.id = prefixes.idOperateur')
        ->orderBy('prefixes.prefixe')
        ->findAll();
}
```
Récupère tous les préfixes **avec le nom de l'opérateur** déjà joint (évite
de faire une requête séparée par ligne dans la vue — mauvaise pratique
appelée « N+1 requêtes »).

### 2.3 `TypeMouvementModel.php`

```php
public function typesTarifables(): array
{
    return $this->where('estTarifable', 1)->orderBy('libelle')->findAll();
}
```
Ne retourne que les types soumis à frais (Retrait, Envoi) — utilisé dans
l'écran des tarifs, car Dépôt (`estTarifable = 0`) n'a pas de grille.

### 2.4 `FraisModel.php` — la grille tarifaire

> **Important : chaque opérateur a sa propre grille tarifaire.** La table
> `frais` porte un `idOperateur`, et le tarif appliqué à un mouvement est
> celui de l'opérateur **du compte qui paie** (voir `frais-fige.md`).

```php
public function listeAvecType(?int $idOperateur = null): array
{
    $requete = $this->select('frais.*, typeMouvement.libelle AS libelleType, operateurs.nom AS nomOperateur')
        ->join('typeMouvement', 'typeMouvement.id = frais.idTypeMouvement')
        ->join('operateurs', 'operateurs.id = frais.idOperateur');

    if ($idOperateur !== null) {
        $requete->where('frais.idOperateur', $idOperateur);
    }

    return $requete->orderBy('operateurs.nom')
        ->orderBy('frais.dateFrais', 'DESC')
        ->orderBy('frais.id', 'DESC')
        ->findAll();
}
```
Liste les tarifs groupés par opérateur, puis du plus récent au plus ancien
(le tri par `id` départage deux lignes insérées le même jour). Le paramètre
`$idOperateur` alimente le filtre de l'écran Tarifs — `null` = tous les
opérateurs, exactement le même principe que le filtre du dashboard.

```php
public function fraisPour(int $idTypeMouvement, float $montant, int $idOperateur, ?string $date = null): float
{
    $date ??= date('Y-m-d H:i:s');   // si $date est null, on prend "maintenant"

    $tranche = $this->where('idOperateur', $idOperateur)
        ->where('idTypeMouvement', $idTypeMouvement)
        ->where('minMontant <=', $montant)
        ->where('maxMontant >=', $montant)
        ->where('dateFrais <=', $date)
        ->orderBy('dateFrais', 'DESC')   // la ligne la plus RÉCENTE...
        ->orderBy('id', 'DESC')
        ->first();                       // ...on ne prend que la première

    return $tranche !== null ? (float) $tranche['montantFrais'] : 0.0;
}
```
C'est la méthode qui retrouve **le tarif en vigueur pour un opérateur à une
date donnée** : parmi les lignes de cet opérateur dont la tranche contient le
montant ET dont `dateFrais` est passée, on prend la plus récente. Deux
mécanismes se superposent donc — la grille par opérateur et son historique.

`$idOperateur` est **obligatoire** (pas de valeur par défaut) : depuis que
chaque opérateur a ses tarifs, un frais sans opérateur n'aurait pas de sens.
Le compilateur force ainsi tous les appelants à préciser lequel.

Elle est appelée par `MouvementModel::retirer()` et `::transferer()` de la
partie client, avec l'opérateur du payeur (voir `partie2-cote-client.md`).

### 2.5 `CompteModel.php` — comptes clients

```php
protected $useTimestamps = true;
protected $dateFormat    = 'datetime';
```
Demande à CodeIgniter de remplir automatiquement `created_at` et
`updated_at` à chaque `insert()`/`update()` — pas besoin de le faire à la main
dans le controller.

```php
public function listeAvecOperateur(?int $idOperateur = null): array
{
    $requete = $this->select('comptes.*, operateurs.nom AS nomOperateur')
        ->join('operateurs', 'operateurs.id = comptes.idOperateur');

    if ($idOperateur !== null) {
        $requete->where('comptes.idOperateur', $idOperateur);
    }

    return $requete->orderBy('comptes.nom')->findAll();
}
```
Le paramètre `?int $idOperateur = null` est la façon dont on a ajouté le
**filtre par opérateur** : si on ne le précise pas (`null`), aucun `WHERE`
supplémentaire n'est ajouté → tous les opérateurs. Si on le précise, on
restreint aux comptes de cet opérateur. C'est le même principe partout où
vous verrez `?int $idOperateur = null` dans ce projet.

```php
public function detailAvecOperateur(int $id): ?array { ... }
```
Un seul compte avec le nom de son opérateur (page `/comptes/{id}`). Retourne
`null` si l'id n'existe pas (`find()` retourne `null` dans ce cas).

```php
public function stats(?int $idOperateur = null): array
{
    $requete = $this->select('COUNT(*) AS total')
        ->select('SUM(estActif = 1) AS actifs')
        ->select('SUM(estActif = 0) AS inactifs')
        ->select('COALESCE(SUM(solde), 0) AS soldeTotal');

    if ($idOperateur !== null) {
        $requete->where('idOperateur', $idOperateur);
    }

    $ligne = $requete->get()->getRowArray();
    ...
}
```
`SUM(estActif = 1)` est une astuce SQLite : `estActif = 1` vaut `1` (vrai) ou
`0` (faux) pour chaque ligne, donc `SUM(...)` compte le nombre de lignes où
c'est vrai — équivalent à `COUNT(*) FILTER (WHERE estActif = 1)` en plus
portable. `COALESCE(SUM(solde), 0)` évite un `NULL` si la table est vide
(un `SUM` sur zéro ligne donne `NULL`, pas `0`).

```php
public function changerActivation(int $id, bool $actif): bool
{
    return $this->update($id, ['estActif' => $actif ? 1 : 0]);
}
```
Utilisé par le bouton Activer/Désactiver (`Comptes::toggle()`).

### 2.6 `MouvementModel.php` — mouvements et gains

C'est le Model le plus riche, car c'est lui qui calcule tout ce qui apparaît
dans le dashboard.

```php
private function filtrerParOperateur(?int $idOperateur)
{
    if ($idOperateur === null) {
        return $this;
    }

    return $this->join('comptes AS sender', 'sender.id = mouvement.idSender', 'left')
        ->join('comptes AS receiver', 'receiver.id = mouvement.idReceiver', 'left')
        ->groupStart()
            ->where('sender.idOperateur', $idOperateur)
            ->orWhere('receiver.idOperateur', $idOperateur)
        ->groupEnd();
}
```
**C'est le cœur du filtre par opérateur ajouté au dashboard.** Explication :

- `mouvement.idSender`/`idReceiver` pointent vers `comptes.id`, mais un
  mouvement n'a pas de colonne opérateur directe.
- On joint donc la table `comptes` **deux fois** avec deux alias différents
  (`sender` pour le compte émetteur, `receiver` pour le compte destinataire)
  — c'est nécessaire en SQL dès qu'on veut joindre la même table deux fois
  dans une requête, avec un rôle différent à chaque fois.
- `'left'` (LEFT JOIN) : indispensable car un dépôt n'a pas de `idSender`
  (`NULL`) et un retrait n'a pas de `idReceiver` (`NULL`) — un JOIN normal
  (INNER) aurait exclu ces lignes.
- `groupStart() ... where() ... orWhere() ... groupEnd()` génère
  `WHERE (sender.idOperateur = X OR receiver.idOperateur = X)` : le
  mouvement est retenu si **l'un des deux comptes** (émetteur ou
  destinataire) appartient à l'opérateur filtré. C'est pour ça qu'un
  mouvement Telma → Orange compte pour Telma **et** pour Orange quand on
  filtre sur l'un ou l'autre (mais une seule fois dans le total « Tous »).
- Cette méthode est `private` et retourne `$this` : elle est faite pour être
  chaînée au milieu d'une requête, comme `->select(...)->filtrerParOperateur($id)->get()`.

```php
public function stats(?int $idOperateur = null): array
{
    $ligne = $this->select('COUNT(*) AS nbMouvements')
        ->select('COALESCE(SUM(mouvement.montant), 0) AS volumeTotal')
        ->select('COALESCE(SUM(mouvement.frais), 0) AS totalGains')
        ->filtrerParOperateur($idOperateur)
        ->get()->getRowArray();
    ...
}
```
`SUM(mouvement.frais)` = les gains de l'opérateur, puisque `frais` est le
frais **figé** au moment du mouvement (voir `frais-fige.md`) : jamais on ne
recalcule depuis la grille `frais`, on lit directement ce qui a été
enregistré.

```php
public function gainsDuJour(?int $idOperateur = null): float
{
    $ligne = $this->select('COALESCE(SUM(mouvement.frais), 0) AS gains')
        ->where('DATE(mouvement.dateMouvement)', date('Y-m-d'))
        ->filtrerParOperateur($idOperateur)
        ->get()->getRowArray();

    return (float) $ligne['gains'];
}
```
`DATE(mouvement.dateMouvement)` extrait juste la date (sans l'heure) d'un
`DATETIME` SQLite, pour comparer avec `date('Y-m-d')` (la date du jour côté
PHP).

```php
public function gainsParJour(?int $idOperateur = null): array
{
    return $this->select('DATE(mouvement.dateMouvement) AS jour')
        ->select('COUNT(*) AS nbMouvements')
        ->select('COALESCE(SUM(mouvement.montant), 0) AS volume')
        ->select('COALESCE(SUM(mouvement.frais), 0) AS gains')
        ->filtrerParOperateur($idOperateur)
        ->groupBy('DATE(mouvement.dateMouvement)')
        ->orderBy('jour', 'DESC')
        ->get()->getResultArray();
}
```
`GROUP BY DATE(dateMouvement)` regroupe toutes les lignes d'une même
journée en une seule ligne de résultat (une par jour), avec les agrégats
(`COUNT`, `SUM`) calculés sur ce groupe. C'est ce qui alimente le tableau
« Historique des gains par jour » du dashboard.

```php
public function detailsDuJour(string $jour, ?int $idOperateur = null): array
{
    $requete = $this->select('mouvement.*, typeMouvement.libelle AS libelleType')
        ->select('sender.numero AS numeroSender, receiver.numero AS numeroReceiver')
        ->join('typeMouvement', 'typeMouvement.id = mouvement.idTypeMouvement');

    if ($idOperateur === null) {
        $requete->join('comptes AS sender', 'sender.id = mouvement.idSender', 'left')
            ->join('comptes AS receiver', 'receiver.id = mouvement.idReceiver', 'left');
    } else {
        $requete->filtrerParOperateur($idOperateur);
    }

    return $requete->where('DATE(mouvement.dateMouvement)', $jour)
        ->orderBy('mouvement.dateMouvement', 'DESC')
        ->findAll();
}
```
Ici on a besoin des numéros de téléphone (`sender.numero`,
`receiver.numero`) pour l'affichage, donc les jointures vers `comptes` sont
**toujours** nécessaires — filtré ou non. Le `if/else` évite juste de les
faire deux fois : si un filtre est actif, `filtrerParOperateur()` les fait
déjà (avec en plus le `WHERE`) ; sinon on les ajoute nous-mêmes sans filtre.

```php
public function mouvementsDuCompte(int $idCompte): array { ... }
```
Les mouvements (entrants + sortants) d'un compte précis, pour la page
`/comptes/{id}`. Pas de filtre opérateur ici : on est déjà sur *un* compte
particulier, donc son opérateur est implicite.

```php
public function statsParType(?int $idOperateur = null): array { ... }
```
Même logique que `gainsParJour()` mais groupé par `idTypeMouvement` au lieu
de la date — alimente le tableau « Répartition par type d'opération ».

---

## 3. Les Controllers (`app/Controllers/`)

Un controller ne fait **jamais** de SQL : il appelle des méthodes de Model,
et prépare des données pour une vue. Regardez chaque méthode publique : elle
correspond exactement à une route dans `Routes.php`.

### 3.1 `Prefixes.php` — CRUD simple

Le patron qui revient pour **tous les CRUD** du projet :

- `index()` → liste (GET `/prefixes`)
- `store()` → création (POST `/prefixes`)
- `edit($id)` → formulaire de modification pré-rempli (GET `/prefixes/{id}/edit`)
- `update($id)` → enregistre la modification (POST `/prefixes/{id}`)
- `delete($id)` → suppression (POST `/prefixes/{id}/delete`)

```php
public function store()
{
    $prefixes = model(PrefixeModel::class);

    if (! $prefixes->insert($this->request->getPost(['prefixe', 'idOperateur']))) {
        return redirect()->to('prefixes')->withInput()->with('errors', $prefixes->errors());
    }

    return redirect()->to('prefixes')->with('success', 'Préfixe ajouté.');
}
```
`insert()` retourne `false` si la validation (définie dans le Model) échoue.
Dans ce cas : on repart en arrière avec `withInput()` (pour réafficher ce
que l'utilisateur avait tapé via `old()`) et les erreurs en flashdata. Sinon,
message de succès. **Ce patron est identique dans `Tarifs::store()` et
`Comptes::store()`.**

### 3.2 `Tarifs.php`

Même structure que `Prefixes`, avec deux particularités : chaque tarif
appartient à un **opérateur**, et sa date d'entrée en vigueur est
optionnelle.

`index()` reprend le principe du filtre du dashboard :

```php
public function index(): string
{
    $idOperateur = $this->idOperateurFiltre();   // ?operateur=ID, null si absent

    return view('tarifs/index', [
        'tarifs'      => model(FraisModel::class)->listeAvecType($idOperateur),
        'types'       => model(TypeMouvementModel::class)->typesTarifables(),
        'operateurs'  => model(OperateurModel::class)->orderBy('nom')->findAll(),
        'idOperateur' => $idOperateur,
    ]);
}
```

`$operateurs` sert à la fois au menu déroulant du filtre et à celui du
formulaire d'ajout. `$idOperateur` est renvoyé à la vue pour garder l'option
sélectionnée **et** pré-remplir l'opérateur du formulaire — quand on filtre
sur Orange, le tarif qu'on ajoute vise Orange par défaut.

```php
private function donneesDuFormulaire(): array
{
    $donnees = $this->request->getPost(['idOperateur', 'idTypeMouvement', 'minMontant', 'maxMontant', 'montantFrais']);

    $date = $this->request->getPost('dateFrais');
    if ($date !== null && $date !== '') {
        // input datetime-local => "Y-m-d\TH:i"
        $donnees['dateFrais'] = str_replace('T', ' ', $date) . (strlen($date) === 16 ? ':00' : '');
    }

    return $donnees;
}
```
Un `<input type="datetime-local">` HTML envoie une valeur au format
`2026-07-15T14:30` (avec un `T` au milieu, sans secondes). SQLite attend
`2026-07-15 14:30:00`. Cette méthode convertit l'un vers l'autre. Si le
champ est laissé vide, on ne met pas `dateFrais` dans le tableau → la
colonne prend sa valeur `DEFAULT CURRENT_TIMESTAMP` définie dans `base.sql`
(donc "maintenant").

Cette méthode privée est appelée à la fois par `store()` et `update()`, pour
ne pas dupliquer la logique.

### 3.3 `Comptes.php`

En plus du CRUD classique :

```php
public function show(int $id)
{
    $compte = model(CompteModel::class)->detailAvecOperateur($id);
    ...
    return view('comptes/show', [
        'compte'     => $compte,
        'mouvements' => model(MouvementModel::class)->mouvementsDuCompte($id),
    ]);
}
```
La page de détail combine deux Models différents (`CompteModel` pour la
fiche, `MouvementModel` pour l'historique) — un controller a le droit
d'appeler plusieurs Models, tant qu'il n'exécute pas de SQL lui-même.

```php
public function toggle(int $id)
{
    ...
    $comptes->changerActivation($id, ! ((int) $compte['estActif'] === 1));

    return redirect()->back()->with('success', 'Compte ' . ((int) $compte['estActif'] === 1 ? 'désactivé' : 'activé') . '.');
}
```
`redirect()->back()` renvoie vers la page d'où venait le clic (utile car le
bouton existe à la fois sur `/comptes` et sur le dashboard).

```php
private function donneesDuFormulaire(): array
{
    $donnees = $this->request->getPost(['numero', 'nom', 'solde', 'idOperateur']);

    $donnees['solde']    = $donnees['solde'] === '' || $donnees['solde'] === null ? 0 : $donnees['solde'];
    $donnees['estActif'] = $this->request->getPost('estActif') !== null ? 1 : 0;

    return $donnees;
}
```
Une case à cocher HTML (`<input type="checkbox">`) **n'envoie rien du tout**
au serveur si elle n'est pas cochée (pas de `estActif=0` automatique) — donc
`getPost('estActif')` vaut `null` si décochée, et la valeur cochée (`'1'`)
sinon. C'est pour ça qu'on teste `!== null` plutôt que la valeur elle-même.

### 3.4 `Dashboard.php` — avec le filtre opérateur

```php
public function index(): string
{
    $idOperateur = $this->idOperateurFiltre();
    $mouvements  = model(MouvementModel::class);

    return view('dashboard/index', [
        'operateurs'      => model(OperateurModel::class)->orderBy('nom')->findAll(),
        'idOperateur'     => $idOperateur,
        'statsMouvements' => $mouvements->stats($idOperateur),
        'gainsDuJour'     => $mouvements->gainsDuJour($idOperateur),
        'gainsParJour'    => $mouvements->gainsParJour($idOperateur),
        'statsParType'    => $mouvements->statsParType($idOperateur),
        'statsComptes'    => model(CompteModel::class)->stats($idOperateur),
        'comptes'         => model(CompteModel::class)->listeAvecOperateur($idOperateur),
    ]);
}

private function idOperateurFiltre(): ?int
{
    $valeur = $this->request->getGet('operateur');

    return ($valeur === null || $valeur === '') ? null : (int) $valeur;
}
```
`idOperateurFiltre()` lit `?operateur=2` dans l'URL. Si absent ou vide →
`null` → « tous les opérateurs » (comportement par défaut, aucun `WHERE`
ajouté dans les Models). Sinon, l'id est transmis **à toutes les méthodes
des Models** qui composent le dashboard — c'est tout : chaque Model sait
déjà quoi faire de ce paramètre (voir section 2.6).

```php
public function gains(string $jour)
{
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)) {
        return redirect()->to('/')->with('error', 'Date invalide.');
    }

    $idOperateur = $this->idOperateurFiltre();

    return view('dashboard/gains', [
        'jour'        => $jour,
        'idOperateur' => $idOperateur,
        'operateurs'  => model(OperateurModel::class)->orderBy('nom')->findAll(),
        'mouvements'  => model(MouvementModel::class)->detailsDuJour($jour, $idOperateur),
    ]);
}
```
`preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)` vérifie que l'URL contient bien
une date au format `AAAA-MM-JJ` avant de l'utiliser dans une requête SQL —
une sécurité simple contre une URL trafiquée. Le filtre opérateur est repris
de la même façon et propagé au Model, pour que la page de détail d'un jour
respecte le filtre choisi sur le dashboard (voir section 4 pour comment le
lien entre les deux pages est fait).

---

## 4. Les Vues (`app/Views/`)

### 4.1 Le layout (`layout/main.php`)

Toutes les vues du projet **étendent** ce layout au lieu de dupliquer le
HTML (sidebar, header, CSS…). Système de « sections » CodeIgniter :

```php
<?= $this->extend('layout/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('subtitle') ?>Vue d'ensemble...<?= $this->endSection() ?>
<?= $this->section('content') ?>
    ... le HTML propre à la page ...
<?= $this->endSection() ?>
```

Dans `layout/main.php`, ces sections sont réinjectées avec
`$this->renderSection('title')` (le titre), et `$this->renderSection('content')`
(le corps de la page) à l'endroit voulu. **Toute nouvelle page doit suivre ce
même schéma** (`extend` + 3 sections `title`/`subtitle`/`content`).

Le lien actif dans la sidebar :
```php
<a href="<?= site_url('prefixes') ?>" class="nav-item <?= url_is('prefixes*') ? 'active' : '' ?>">
```
`url_is('prefixes*')` (fonction CodeIgniter) retourne vrai si l'URL actuelle
commence par `prefixes` — le `*` est un joker, donc `prefixes/3/edit` allume
aussi le lien « Préfixes ».

Les messages flash :
```php
<?php if (session()->getFlashdata('success')) : ?>
    <div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif ?>
```
`session()->getFlashdata('success')` lit **et efface** en même temps la
donnée flash déposée par `->with('success', '...')` dans le controller — un
message flash ne s'affiche donc qu'une fois, juste après le `redirect()`.

### 4.2 Vues des préfixes / tarifs / comptes (CRUD standard)

Toutes suivent le même schéma :

1. Un tableau (`<table class="app-table">`) qui boucle sur les données avec
   `foreach`, en utilisant systématiquement `esc()` pour afficher du texte
   venant de la base (sécurité contre le HTML/JS malveillant).
2. Un formulaire d'ajout (`prefixes/index.php`, `tarifs/index.php`) ou une
   page dédiée (`comptes/create` via `comptes/form.php`), toujours avec
   `<?= csrf_field() ?>` en première ligne du `<form>`.
3. Les erreurs de validation sont affichées ainsi :
   ```php
   <?php if (session()->getFlashdata('errors')) : ?>
       <?php foreach (session()->getFlashdata('errors') as $erreur) : ?>
           <p class="form-error"><?= esc($erreur) ?></p>
       <?php endforeach ?>
   <?php endif ?>
   ```
   (`errors()` du Model retourne un tableau `champ => message`, mais on
   n'affiche ici que les messages, pas les noms de champs.)
4. Chaque `<input>` reprend la valeur précédente avec `old('champ', $valeur_actuelle)`
   — utile pour l'édition (préremplir avec la valeur en base) ET pour
   réafficher une saisie après une erreur de validation.

`comptes/form.php` est **partagé** entre création et édition :
```php
<?php $estEdition = $compte !== null; ?>
...
<form method="post" action="<?= $estEdition ? site_url('comptes/' . $compte['id']) : site_url('comptes') ?>">
```
`Comptes::create()` appelle cette vue avec `'compte' => null` ; `Comptes::edit()`
avec le compte trouvé. La vue adapte titre, action du formulaire et bouton
selon ce cas.

### 4.3 `dashboard/index.php` — avec le sélecteur d'opérateur

```php
<form method="get" action="<?= site_url('/') ?>" class="filter-form">
    <select id="operateur" name="operateur" onchange="this.form.submit()">
        <option value="">Tous les opérateurs</option>
        <?php foreach ($operateurs as $op) : ?>
            <option value="<?= $op['id'] ?>" <?= $idOperateur === (int) $op['id'] ? 'selected' : '' ?>>
                <?= esc($op['nom']) ?>
            </option>
        <?php endforeach ?>
    </select>
</form>
```
Point important : `method="get"` (pas `post`) — un filtre d'affichage n'a
pas besoin de CSRF ni de confirmation, et ça permet de **partager l'URL**
(`?operateur=2`) ou de faire « Précédent » dans le navigateur en gardant le
filtre. `onchange="this.form.submit()"` envoie le formulaire dès qu'on change
l'option, sans bouton « Valider » à cliquer (le `<noscript>` juste après
prévoit un bouton de secours si JavaScript est désactivé).

Le lien vers le détail d'un jour propage le filtre choisi :
```php
<a href="<?= site_url('dashboard/gains/' . $g['jour']) . ($idOperateur !== null ? '?operateur=' . $idOperateur : '') ?>">Détails</a>
```

### 4.4 `dashboard/gains.php`

Reçoit `$mouvements` déjà filtrés par le controller (pas de nouveau filtre
ici, juste l'affichage) et calcule les totaux du jour **côté PHP** avec
`array_sum`/`array_map` — pas une nouvelle requête, puisqu'on a déjà toutes
les lignes en main :
```php
$totalGains  = array_sum(array_map(static fn ($m) => (float) $m['frais'], $mouvements));
```
Affiche également un badge rappelant l'opérateur filtré, en le retrouvant
dans la liste `$operateurs` passée par le controller.

---

## 5. Les Routes (`app/Config/Routes.php`)

```php
$routes->get('/', 'Dashboard::index');
$routes->get('dashboard/gains/(:segment)', 'Dashboard::gains/$1');
```
`(:segment)` capture un morceau d'URL (texte quelconque, ici la date) et le
transmet en paramètre `$1` à la méthode du controller. Pour un id numérique,
on utilise `(:num)` (ex. `prefixes/(:num)/edit`).

Le motif qui revient pour chaque ressource (préfixes, tarifs, comptes) :

| Verbe | URL | Rôle |
|---|---|---|
| GET | `/xxx` | liste |
| POST | `/xxx` | créer |
| GET | `/xxx/{id}/edit` | formulaire de modification |
| POST | `/xxx/{id}` | enregistrer la modification |
| POST | `/xxx/{id}/delete` | supprimer |

On utilise `GET` pour tout ce qui **affiche** une page, et `POST` pour tout
ce qui **modifie** la base (créer/modifier/supprimer/activer) — jamais l'
inverse, pour rester conforme aux conventions HTTP et éviter qu'un simple
clic sur un lien ou un rechargement de page ne déclenche une suppression.

---

## 6. Comment ajouter des choses (guide pratique)

### Ajouter une nouvelle statistique au dashboard

1. Ajouter une méthode au Model concerné (ex. `MouvementModel::maStat(?int $idOperateur = null)`),
   en suivant le même principe de filtre optionnel si elle doit répondre au
   sélecteur d'opérateur.
2. L'appeler dans `Dashboard::index()` et l'ajouter au tableau passé à `view()`.
3. L'afficher dans `dashboard/index.php`, dans une `<div class="stat-card">`.

### Ajouter un nouveau filtre (ex. filtrer aussi par type de mouvement)

Même principe que le filtre opérateur : un paramètre optionnel de plus dans
les méthodes du Model, lu depuis `$this->request->getGet(...)` dans le
controller, transmis à la vue pour construire le `<select>`, et repris dans
les liens qui changent de page (comme `operateur` l'est déjà).

### Ajouter une nouvelle page (CRUD ou autre)

1. **Route** dans `Routes.php`.
2. **Controller** : une méthode par route, qui n'appelle QUE des Models.
3. **Model** : si une requête n'existe pas encore, ajouter la méthode ici —
   jamais de SQL dans le controller/la vue.
4. **Vue** qui `extend('layout/main')` avec les 3 sections `title`/`subtitle`/`content`.
5. **Lien dans la sidebar** (`layout/main.php`) si la page doit être accessible
   depuis le menu.

### Ajouter une colonne à une table existante

Voir `sqlite-setup.md` (section « Modifier le schéma »). Une fois la colonne
ajoutée dans `base.sql` et la base recréée, il faut aussi :
- l'ajouter à `$allowedFields` du Model concerné (sinon `insert()`/`update()`
  l'ignore silencieusement) ;
- ajouter une règle dans `$validationRules` si elle doit être validée ;
- l'ajouter dans le formulaire (vue) et dans la méthode
  `donneesDuFormulaire()` du controller si elle vient d'un `<input>`.
