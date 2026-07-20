# Partie 2 — Côté client : explication complète du code

Ce document explique **tout le code de l'espace client**, fichier par fichier
et méthode par méthode, pour pouvoir le relire, le modifier et l'étendre.

Il suppose acquis les bases de CodeIgniter présentées dans
`partie1-cote-operateur.md` (Models / Controllers / Vues / Routes, Query
Builder, `esc()`, `site_url()`, flash messages). On ne réexplique ici que ce
qui est **nouveau** dans la partie client : la **session**, les
**transactions**, et le retour `['success' => …, 'message' => …]`.

> **Règle du projet, toujours valable :** aucune requête SQL dans un
> controller ou une vue. Tout accès à la base passe par un Model.

---

## 0. Vue d'ensemble

L'espace client est une interface **pour le titulaire d'un compte** (par
opposition à la partie 1, qui est l'interface d'administration de
l'opérateur). Un client se connecte avec son numéro, consulte son solde, et
effectue trois opérations : **dépôt**, **retrait**, **transfert**.

| Fichier | Rôle |
|---|---|
| `app/Controllers/AuthController.php` | Connexion / déconnexion |
| `app/Controllers/ClientController.php` | Profil, historique |
| `app/Controllers/MouvementController.php` | Dépôt, retrait, transfert |
| `app/Models/CompteModel.php` | (complété) `getNom()`, `getNum()`, `getSolde()` |
| `app/Models/MouvementModel.php` | (complété) `deposer()`, `retirer()`, `transferer()` |
| `app/Models/TypeMouvementModel.php` | (complété) `idParLibelle()` |
| `app/Views/auth/login.php` | Page de connexion |
| `app/Views/layout/client.php` | Layout de l'espace client |
| `app/Views/clients/*.php` | Profil, historique, dépôt, retrait, transfert |

Le flux général d'une opération :

```
Vue (formulaire)  →  Controller  →  Model (transaction SQL)  →  retour tableau
                                                                      ↓
                        redirect + flash message  ←──────────────────
```

---

## 1. La session : comment le client reste connecté

### Le principe

HTTP est **sans mémoire** : chaque requête est indépendante, le serveur ne
« sait » pas qui vous êtes. La **session** résout ça : à la connexion, on
stocke l'identité du client côté serveur, et le navigateur reçoit un cookie
qui sert de ticket pour retrouver ces données à chaque requête suivante.

Dans CodeIgniter, on manipule la session avec la fonction `session()` :

```php
session()->set('user', [...]);   // écrire
session()->get('user');          // lire (null si absent)
session()->destroy();            // tout effacer (déconnexion)
```

### `AuthController::login()`

```php
public function login()
{
    $numero = trim((string) $this->request->getPost('numero'));

    if ($numero === '') {
        return redirect()->back()->withInput()->with('erreur', 'Veuillez renseigner le numéro.');
    }

    $user = model(CompteModel::class)->where('numero', $numero)->first();

    if (!$user) {
        return redirect()->back()->withInput()->with('erreur', 'Numéro introuvable.');
    }

    if (array_key_exists('estActif', $user) && (int) $user['estActif'] !== 1) {
        return redirect()->back()->withInput()->with('erreur', 'Compte inactif.');
    }

    session()->set('user', [
        'id'     => $user['id'],
        'nom'    => $user['nom'],
        'numero' => $user['numero'],
    ]);

    return redirect()->to('/profil');
}
```

Trois vérifications successives, chacune avec son message :

1. **numéro vide** → on ne va même pas en base ;
2. **numéro introuvable** → aucun compte avec ce numéro ;
3. **compte inactif** (`estActif = 0`) → connexion refusée. C'est le lien
   direct avec la colonne `estActif` gérée par l'opérateur en partie 1 :
   quand l'opérateur désactive un compte, son titulaire ne peut plus se
   connecter.

On ne stocke en session que **l'essentiel** (`id`, `nom`, `numero`) — surtout
**pas le solde**, qui change à chaque opération et doit toujours être relu en
base pour être juste.

> **Note :** ce projet ne gère pas de mot de passe (connexion par numéro
> seul). Pour en ajouter un, voir la section 7.

### `AuthController::logout()`

```php
session()->destroy();
return redirect()->to('/login');
```

`destroy()` supprime toute la session : le client redevient anonyme.

### Protéger une page

Il n'y a pas de filtre global : **chaque méthode vérifie elle-même** la
session. Dans `ClientController` :

```php
$user = session()->get('user');

if (! $user) {
    return redirect()->to('/login');
}
```

Dans `MouvementController`, cette vérification est factorisée dans une
méthode privée `clientConnecte()` :

```php
private function clientConnecte()
{
    $user = session()->get('user');

    if (! $user) {
        return redirect()->to('/login');
    }

    $client = model(CompteModel::class)->find($user['id']);

    if (! $client) {
        return redirect()->to('/login')->with('erreur', 'Client introuvable.');
    }

    return $client;   // tableau du compte, relu en base (solde à jour)
}
```

Elle retourne **soit un tableau** (le compte), **soit une redirection**.
D'où le test dans chaque méthode qui l'appelle :

```php
$client = $this->clientConnecte();

if (! is_array($client)) {
    return $client;   // c'est une redirection => on la renvoie telle quelle
}
```

C'est un peu inhabituel, mais efficace : une seule ligne protège la page.
Point important : `clientConnecte()` **relit le compte en base** à chaque
appel, donc le solde affiché est toujours le vrai solde actuel.

---

## 2. Les ajouts dans les Models

### 2.1 `TypeMouvementModel::idParLibelle()`

```php
public function idParLibelle(string $libelle): ?int
{
    $type = $this->where('libelle', $libelle)->first();

    return $type !== null ? (int) $type['id'] : null;
}
```

Les opérations ont besoin de l'`id` du type (« Dépôt », « Retrait »,
« Envoi »). Plutôt que d'écrire `1`, `2`, `3` en dur dans le code — ce qui
casserait si l'ordre des `INSERT` de `base.sql` changeait —, on retrouve
l'id **par son libellé**.

> ⚠️ Les libellés doivent correspondre **exactement** à ceux de `base.sql`,
> accents compris : `'Dépôt'`, `'Retrait'`, `'Envoi'`.

### 2.2 `CompteModel` : `getNom()`, `getNum()`, `getSolde()`

```php
public function getSolde(int $id): ?float
{
    $compte = $this->select('solde')->find($id);

    return $compte === null ? null : (float) $compte['solde'];
}
```

Trois accesseurs simples, sur le même modèle. `select('solde')` ne récupère
que la colonne utile. Le `?float` (type **nullable**) et le test
`=== null` évitent une erreur si l'id n'existe pas : la fonction retourne
`null` plutôt que de planter.

---

## 3. Les opérations : `MouvementModel`

C'est le cœur de la partie client. Les trois méthodes suivent **la même
structure en 4 temps** :

```
1. Valider (montant > 0, compte existe, solde suffisant…)
2. Calculer les frais en vigueur
3. Transaction : insérer le mouvement + mettre à jour le(s) solde(s)
4. Retourner ['success' => bool, 'message' => string]
```

### 3.1 Le retour `['success' => …, 'message' => …]`

Les méthodes ne retournent ni `true`/`false` ni une exception, mais un
**tableau** :

```php
return ['success' => false, 'message' => 'Le montant doit être positif.'];
```

Avantage : le Model décide du message (il connaît le détail : le montant des
frais, le solde manquant…) et le controller n'a plus qu'à l'afficher, sans
rien recalculer :

```php
return redirect()->to('/client/depot')
    ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
```

Une seule ligne gère le succès **et** l'échec : la clé du flash est
`'success'` ou `'erreur'` selon le booléen.

### 3.2 Les transactions — le point le plus important

Un retrait, c'est **deux** écritures : insérer le mouvement **et** diminuer
le solde. Si la seconde échouait (coupure, erreur), on aurait un mouvement
enregistré sans débit correspondant : la base serait **incohérente**.

La transaction rend les deux écritures **indissociables** — soit les deux
réussissent, soit aucune :

```php
$this->db->transStart();          // début

$this->insert([...]);             // écriture 1 : le mouvement
$comptes->update($idCompte, [...]); // écriture 2 : le solde

$this->db->transComplete();       // fin : valide, ou annule tout

if ($this->db->transStatus() === false) {
    return ['success' => false, 'message' => 'Erreur lors du retrait.'];
}
```

- `transStart()` ouvre la transaction ;
- `transComplete()` valide (COMMIT) si tout s'est bien passé, **annule tout**
  (ROLLBACK) sinon ;
- `transStatus()` dit si ça a réussi.

C'est **obligatoire** dès qu'une opération touche plusieurs lignes. Le
transfert en modifie trois (le mouvement, le solde émetteur, le solde
destinataire) : sans transaction, un plantage au mauvais moment ferait
disparaître de l'argent.

### 3.3 `deposer()`

```php
public function deposer(int $idCompte, float $montant): array
```

Le dépôt est le cas le plus simple :

- montant strictement positif, compte existant ;
- `idSender = null` (l'argent vient de l'extérieur), `idReceiver = $idCompte` ;
- **`frais = 0`** : « Dépôt » a `estTarifable = 0` dans `base.sql`, aucun
  frais n'est prélevé ;
- solde : `solde + montant`.

### 3.4 `retirer()`

```php
$idType = model(TypeMouvementModel::class)->idParLibelle('Retrait');
// Tarif de l'opérateur du titulaire du compte : chaque opérateur a sa grille
$frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $compte['idOperateur']);
$total = $montant + $frais;

if ($compte['solde'] < $total) {
    return ['success' => false, 'message' => 'Solde insuffisant (montant + frais de ' . ... . ' Ar).'];
}
```

Deux différences avec le dépôt :

1. **Les frais sont calculés** via `FraisModel::fraisPour()` — la méthode de
   la partie 1, qui retourne le tarif **en vigueur aujourd'hui** pour la
   tranche du montant (voir `frais-fige.md`).
2. **Le solde doit couvrir montant + frais**, pas seulement le montant.
   Le contrôle porte bien sur `$total`.

⚠️ **Chaque opérateur a sa propre grille tarifaire**, d'où le 3ᵉ argument
`$compte['idOperateur']`. Pour un retrait, c'est l'opérateur du titulaire du
compte qui s'applique, puisque c'est lui qui est débité. Un retrait de
5 000 Ar coûte ainsi 200 Ar chez Telma, 250 chez Orange et 150 chez Airtel.

Écritures : `idSender = $idCompte`, `idReceiver = null`, et
`solde - ($montant + $frais)`.

Le frais calculé est **copié dans `mouvement.frais`** : c'est le snapshot
figé. Si la grille change demain, ce retrait gardera le frais d'aujourd'hui.

### 3.5 `transfererMultiple()`

```php
public function transfererMultiple(
    int $idSender,
    array $numerosReceivers,
    float $montantTotal,
    bool $retraitInclus = false
): array
```

Le montant saisi est un **montant total à répartir**. Le Model calcule d'abord :

```php
$montantPart = $montantTotal / count($numerosReceivers);
```

Les frais ne sont jamais calculés sur `$montantTotal`. Pour chaque
numéro, le Model applique la grille à `$montantPart`, calcule éventuellement
la commission inter-opérateurs et les frais de retrait offerts, puis additionne
les résultats. Avec 30 000 Ar et 3 destinataires, chaque destinataire reçoit
une part de 10 000 Ar et le frais de la tranche 10 000 Ar est appliqué trois
fois.

Les validations portent sur le montant, l'existence et l'activation de tous
les comptes, les doublons, le transfert vers soi-même et le solde nécessaire
pour couvrir la totalité des parts, frais et commissions.

Toutes les insertions de mouvements, tous les crédits destinataires et le
débit unique de l'émetteur sont exécutés dans **une seule transaction**. Une
erreur sur un seul numéro annule donc le transfert complet. La méthode
`transferer()` reste disponible et délègue simplement à
`transfererMultiple()` avec un tableau contenant un seul numéro.

### Quel opérateur tarife un transfert ?

C'est une conséquence directe du point précédent : puisque l'émetteur paie,
c'est **la grille de l'opérateur de l'émetteur** qui s'applique.

```php
$idType = model(TypeMouvementModel::class)->idParLibelle('Envoi');
// Tarif de l'opérateur de l'ÉMETTEUR : c'est lui qui paie les frais
$frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $sender['idOperateur']);
```

Un transfert **Telma → Orange** applique donc le tarif **Telma**, même si le
destinataire est chez Orange. Vérifié sur un envoi de 6 000 Ar :

| Émetteur | Destinataire | Frais appliqué |
|---|---|---|
| Telma | Orange | 150 Ar (tarif Telma) |
| Orange | Telma | 80 Ar (tarif Orange) |
| Airtel | Telma | 120 Ar (tarif Airtel) |

C'est cohérent avec le sens métier : le frais est le gain que l'opérateur
réalise sur **son propre client**.

---

## 4. Les Controllers

### 4.1 `ClientController`

Deux méthodes, `profile()` et `historique()`, toutes deux bâties pareil :
vérifier la session → relire le compte → passer les données à la vue.

```php
return view('clients/historique', [
    'client'     => $client,
    'mouvements' => model(MouvementModel::class)->mouvementsDuCompte($user['id']),
]);
```

`mouvementsDuCompte()` est la méthode **déjà écrite en partie 1** (elle sert
aussi à la page de détail d'un compte côté opérateur) : elle récupère les
mouvements où le compte est émetteur **ou** destinataire. Bon exemple de
réutilisation — on n'a rien eu à ajouter dans le Model.

### 4.2 `MouvementController`

Six méthodes, par paires : `xxxForm()` affiche le formulaire (GET),
`xxx()` traite l'envoi (POST).

```php
public function depot()
{
    $client = $this->clientConnecte();
    if (! is_array($client)) { return $client; }

    $montant  = (float) $this->request->getPost('montant');
    $resultat = model(MouvementModel::class)->deposer($client['id'], $montant);

    return redirect()->to('/client/depot')
        ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
}
```

Le controller est volontairement **très court** : il vérifie la session,
récupère les champs du formulaire, appelle le Model, redirige avec le
message. **Aucun calcul de frais, aucune requête SQL** — toute la logique
métier est dans le Model.

Le `redirect()` après un POST n'est pas un détail : il évite qu'un
rafraîchissement de page (F5) ne renvoie le formulaire et refasse
l'opération une deuxième fois.

---

## 5. Les Vues

### 5.1 `layout/client.php` — un layout séparé

L'espace client a **son propre layout**, distinct de `layout/main.php` (celui
de l'opérateur), parce que la navigation n'a rien à voir :

| | Sidebar |
|---|---|
| `layout/main.php` (opérateur) | Dashboard, Comptes clients, Préfixes, Tarifs |
| `layout/client.php` (client) | Mon profil, Historique, Dépôt, Retrait, Transfert |

Le layout client ajoute deux choses :

**Un bouton de déconnexion** (en POST, car il modifie l'état du serveur) :

```php
<form action="<?= site_url('logout') ?>" method="post">
    <?= csrf_field() ?>
    <button type="submit" class="logout-btn">Se déconnecter</button>
</form>
```

**L'identité du client dans l'en-tête**, lue directement en session :

```php
<?php $connecte = session()->get('user'); ?>
<div class="user-avatar"><?= esc(mb_strtoupper(mb_substr($connecte['nom'] ?? 'C', 0, 2))) ?></div>
<span class="user-name"><?= esc($connecte['nom'] ?? '') ?></span>
```

`mb_substr(..., 0, 2)` prend les 2 premières lettres du nom pour l'avatar
(« Rakoto » → « RA »). Les fonctions `mb_*` gèrent correctement les accents.
`?? ''` évite une erreur si la clé manque.

**Attention aux deux clés de flash :** les controllers de la partie client
utilisent `'erreur'`, ceux de la partie 1 utilisent `'error'`. Le layout
client affiche **les deux**, plus `'success'` :

```php
<?php if (session()->getFlashdata('erreur')) : ?>
    <div class="flash flash-error"><?= esc(session()->getFlashdata('erreur')) ?></div>
<?php endif ?>
```

### 5.2 `auth/login.php` — page hors layout

La page de connexion **n'étend aucun layout** : elle est autonome, car elle
ne doit afficher ni sidebar ni menu (le visiteur n'est pas encore connecté).
Elle reprend `templatemo-crypto-login.css` du template, avec sa mise en page
en deux colonnes : présentation à gauche (`.auth-branding`), formulaire à
droite (`.auth-form-container`).

Le champ conserve la saisie en cas d'erreur grâce à `old()` :

```php
<input type="text" name="numero" value="<?= esc(old('numero')) ?>" required autofocus>
```

C'est aussi **la page d'accueil du site** (route `/`), avec en bas un lien
vers `site_url('dashboard')` pour basculer sur l'espace opérateur.

### 5.3 `clients/profile.php`

Quatre `stat-card` (solde, numéro, statut, date d'ouverture), puis les
informations détaillées et des boutons d'accès rapide aux opérations. Le
statut réutilise les badges de la partie 1 :

```php
<?php if ((int) $client['estActif'] === 1) : ?>
    <span class="badge badge-actif">Actif</span>
<?php else : ?>
    <span class="badge badge-inactif">Désactivé</span>
<?php endif ?>
```

### 5.4 `clients/depot.php`, `retrait.php`, `transfert.php`

Les trois suivent la même structure : formulaire à gauche, solde actuel et
explication à droite. Chacune rappelle sa règle de tarification (le dépôt est
gratuit, le retrait et le transfert ont des frais).

Le transfert a un champ de plus, le numéro du destinataire :

```php
<input type="text" id="numeroReceiver" name="numeroReceiver" placeholder="Ex. 0344455667" required>
```

`type="number"` avec `min="1"` bloque les montants négatifs **côté
navigateur**. Ce n'est qu'un confort : la vraie validation reste celle du
Model, car un navigateur peut être contourné.

### 5.5 `clients/historique.php`

Le tableau des mouvements, plus quatre totaux (reçu, envoyé, frais payés).
Ces totaux sont calculés **en PHP dans la vue**, à partir des mouvements
déjà chargés — sans aucune requête supplémentaire :

```php
foreach ($mouvements as $m) {
    if ((int) $m['idSender'] === (int) $client['id']) {
        $totalSortant += (float) $m['montant'];
        $totalFrais   += (float) $m['frais'];
    } else {
        $totalEntrant += (float) $m['montant'];
    }
}
```

La variable `$estEmis` détermine le sens de chaque ligne (sortant/entrant),
ce qui colore le montant en rouge ou vert et choisit la bonne contrepartie
à afficher. C'est la même logique que `comptes/show.php` en partie 1.

---

## 6. Les routes

```php
// Authentification
$routes->get('/',       'AuthController::showLoginForm');  // page d'accueil
$routes->get('login',   'AuthController::showLoginForm');  // même page, URL explicite
$routes->post('login',  'AuthController::login');
$routes->post('logout', 'AuthController::logout');

// Espace client — identité
$routes->get('profil',     'ClientController::profile');
$routes->get('historique', 'ClientController::historique');

// Espace client — opérations
$routes->get('client/depot',      'MouvementController::depotForm');
$routes->post('client/depot',     'MouvementController::depot');
$routes->get('client/retrait',    'MouvementController::retraitForm');
$routes->post('client/retrait',   'MouvementController::retrait');
$routes->get('client/transfert',  'MouvementController::transfertForm');
$routes->post('client/transfert', 'MouvementController::transfert');
```

Le schéma **même URL, deux verbes** : `GET` affiche le formulaire, `POST`
le traite. C'est la convention REST, déjà utilisée en partie 1.

`logout` est en `POST` uniquement : une action qui modifie l'état ne doit
jamais être accessible par un simple lien `GET` (sinon n'importe quelle image
pointant vers `/logout` déconnecterait le visiteur).

### La connexion est la page d'accueil

`/` affiche le formulaire de connexion : c'est le point d'entrée de
l'application. Le dashboard opérateur, lui, est passé sur **`/dashboard`**.

⚠️ **Les deux routes `/` et `/login` sont nécessaires**, et pas par confort :
les controllers client font `redirect()->to('/login')` à **six endroits**
(`ClientController` ×4, `MouvementController` ×2) dès que la session est
absente. Si la route `GET login` est supprimée, toutes ces redirections
tombent sur une **404** et l'espace client devient inaccessible.

C'est exactement ce qui s'est produit : en faisant de `/` la page de
connexion, la route `GET login` avait été retirée, ce qui a cassé :

| Élément | Effet |
|---|---|
| `/profil`, `/historique`, les 3 opérations | 404 au lieu du formulaire de connexion |
| Lien « Dashboard » de la sidebar opérateur | renvoyait vers le login |
| Formulaire de filtre du dashboard | filtrer par opérateur renvoyait au login |
| « Retour au dashboard » (page des gains) | renvoyait au login |
| Redirection « date invalide » | renvoyait au login |

**Règle à retenir :** si on change une route, il faut vérifier tous les
`redirect()->to(...)` des controllers **et** tous les `site_url(...)` des
vues qui la visent :

```bash
grep -rn "to('/login')" app/Controllers/
grep -rn "site_url('/')" app/Views/
```

### Naviguer entre les deux espaces

Les deux interfaces sont reliées dans les deux sens :

- **Client → opérateur** : la page de connexion a un lien « accéder au
  dashboard » en bas (`auth/login.php`), vers `site_url('dashboard')`.
- **Opérateur → client** : la sidebar opérateur (`layout/main.php`) a un
  bouton **« Quitter l'espace opérateur »** vers `site_url('login')`.

Différence importante avec la déconnexion client : ce bouton opérateur est
un simple lien `<a>`, **pas** un formulaire POST, car l'espace opérateur n'a
aucune session — il n'y a rien à détruire. Le bouton de déconnexion du
client, lui, reste en POST puisqu'il appelle `session()->destroy()`.

---

## 7. Comment modifier / ajouter des choses

### Ajouter un mot de passe à la connexion

1. `base.sql` : ajouter `motDePasse TEXT` à `comptes`, recréer la base.
2. `CompteModel` : ajouter `'motDePasse'` dans `$allowedFields`.
3. À la création d'un compte, hacher : `password_hash($mdp, PASSWORD_DEFAULT)`.
   **Ne jamais stocker un mot de passe en clair.**
4. Dans `AuthController::login()`, après avoir trouvé `$user` :

```php
if (! password_verify($this->request->getPost('motDePasse'), $user['motDePasse'])) {
    return redirect()->back()->withInput()->with('erreur', 'Mot de passe incorrect.');
}
```

5. Ajouter le champ dans `auth/login.php`.

### Ajouter une nouvelle opération (ex. paiement de facture)

1. `base.sql` : `INSERT INTO typeMouvement (libelle, estTarifable) VALUES ('Paiement', 1);`
   plus ses tranches dans `frais`.
2. `MouvementModel` : une méthode `payer()` copiée sur `retirer()`, avec
   `idParLibelle('Paiement')`. **Garder la transaction.**
3. `MouvementController` : les deux méthodes `payerForm()` / `payer()`.
4. `Routes.php` : les routes GET et POST.
5. Une vue `clients/paiement.php` copiée sur `retrait.php`, et un lien dans
   la sidebar de `layout/client.php`.

### Protéger les pages avec un filtre plutôt qu'à la main

La vérification de session est répétée dans chaque méthode. Pour la
centraliser, créer un filtre (`app/Filters/AuthFilter.php`) et l'appliquer
dans `Routes.php` :

```php
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('profil', 'ClientController::profile');
    // …
});
```

C'est plus propre dès que le nombre de pages protégées augmente.

### Empêcher un solde négatif au niveau de la base

Les contrôles sont faits en PHP. Pour une sécurité supplémentaire, ajouter
une contrainte dans `base.sql` :

```sql
solde REAL NOT NULL DEFAULT 0 CHECK (solde >= 0),
```

SQLite refusera alors physiquement toute écriture rendant un solde négatif.

---

## 8. Vérifications effectuées

Parcours complet testé sur le serveur local, base restaurée ensuite :

| Test | Résultat |
|---|---|
| Connexion `0341112233` | ✅ redirection vers `/profil` |
| Dépôt 50 000 | ✅ 150 000 → 200 000 (frais 0) |
| Retrait 20 000 | ✅ 200 000 → 179 500 (frais 500) |
| Transfert 5 000 | ✅ émetteur −5 150, destinataire +5 000 (frais **150**) |
| Montant 0 / négatif | ✅ « Le montant doit être positif. » |
| Solde insuffisant | ✅ refusé, solde inchangé |
| Destinataire inconnu | ✅ « Le numéro destinataire est introuvable. » |
| Transfert vers soi-même | ✅ refusé |
| Accès sans connexion | ✅ les 5 pages redirigent vers `/login` |
| Connexion compte inactif | ✅ « Compte inactif. » |
| Déconnexion | ✅ session détruite, accès refusé ensuite |

Le transfert a appliqué un frais de **150** et non 100 : c'est le tarif entré
en vigueur le 15/07 dans `base.sql`. Cela confirme que la grille datée et le
frais figé de la partie 1 fonctionnent bien avec les opérations client.

### Tarifs par opérateur — vérifié

Un **retrait de 5 000 Ar** par un client de chaque opérateur donne bien trois
frais différents, conformes à chaque grille :

| Client | Opérateur | Solde avant → après | Frais |
|---|---|---|---|
| Rakoto Jean | Telma | 150 000 → 144 800 | **200 Ar** |
| Rasoa Marie | Orange | 82 000 → 76 750 | **250 Ar** |
| Rabe Paul | Airtel | 41 000 → 35 850 | **150 Ar** |

Et un **envoi de 6 000 Ar** applique bien le tarif de l'émetteur, y compris
vers un autre opérateur (150 / 80 / 120 — voir le tableau de la section 3.5).

### Un point à connaître

`FraisModel::fraisPour()` retourne **0** quand aucune tranche ne correspond :
montant au-dessus de la tranche maximale (un retrait de 999 999 Ar est donc
sans frais), **ou opérateur n'ayant pas de grille pour ce type**. Ce n'est
pas un bug du code client mais une conséquence des données : pour l'éviter,
ajouter une tranche haute (`200001` → `99999999`) à la grille de **chaque**
opérateur, et penser à créer une grille dès qu'on ajoute un opérateur.
