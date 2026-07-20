# Partie 3 — Les échanges entre opérateurs

Jusqu'ici, chaque opérateur vivait dans son coin. Cette partie ajoute tout ce
qui concerne les **échanges entre opérateurs** : reconnaître un numéro
étranger, facturer une commission, savoir d'où viennent les gains, et savoir
combien on doit à chacun.

---

## 0. Les règles de gestion

Un transfert **Telma → Orange** de 6 000 Ar, avec un frais Telma de 150 Ar et
une commission Orange de 2 % :

```
Client Telma débité   : 6 000 + 150 + 120 = 6 270 Ar
Client Orange crédité : 6 000 Ar                      (le montant exact)

Telma garde  :   150 Ar   (le FRAIS — opérateur d'ORIGINE)
Orange garde :   120 Ar   (la COMMISSION — opérateur d'ARRIVÉE, son taux)
Telma doit à Orange : 6 120 Ar   (montant + commission)
```

Les trois règles à retenir :

1. **Le frais** (fixe, par tranche) est gardé par l'opérateur de l'**émetteur**.
2. **La commission** (en %) est gardée par l'opérateur du **destinataire**,
   à **son** taux, et **uniquement** si les deux opérateurs sont différents.
3. **La commission est prélevée en plus** : le destinataire reçoit toujours le
   montant exact, c'est l'émetteur qui paie tout.

Vérification que le compte tombe juste : Telma encaisse 6 270, garde 150,
reverse 6 120. Orange reçoit 6 120, crédite 6 000 à son client, garde 120. ✓

---

## 1. Le schéma

Deux colonnes ajoutées :

```sql
CREATE TABLE operateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    -- % prélevé sur l'argent ENTRANT depuis un autre opérateur. 2.0 = 2 %
    pourcentageCommission REAL NOT NULL DEFAULT 0
);

CREATE TABLE mouvement (
    ...
    frais      REAL NOT NULL DEFAULT 0,   -- gardé par l'opérateur d'ORIGINE
    commission REAL NOT NULL DEFAULT 0,   -- gardée par l'opérateur d'ARRIVÉE
    ...
);
```

**Pourquoi deux colonnes séparées et pas un seul total ?** Parce que les deux
sommes vont à **deux opérateurs différents**. Si on les additionnait, on ne
pourrait plus ni répartir les gains (point 3) ni calculer ce que chacun doit
(point 4). La commission est **figée** dans le mouvement, exactement comme le
frais : changer le taux demain ne réécrit pas le passé.

---

## 2. Les préfixes : reconnaître l'opérateur d'un numéro

La table `prefixes` existait déjà mais ne servait à rien. Elle sert maintenant
à savoir **à quel opérateur appartient un numéro** — y compris les numéros des
autres opérateurs (032 → Orange, 033 → Airtel…).

```php
// PrefixeModel
public function operateurPourNumero(string $numero): ?array
{
    // Le préfixe le plus LONG d'abord, au cas où deux se chevauchent
    $prefixes = $this->select('prefixes.*, operateurs.nom AS nomOperateur')
        ->join('operateurs', 'operateurs.id = prefixes.idOperateur')
        ->orderBy('LENGTH(prefixes.prefixe)', 'DESC')
        ->findAll();

    foreach ($prefixes as $prefixe) {
        if (str_starts_with($numero, $prefixe['prefixe'])) {
            return $prefixe;
        }
    }

    return null;
}
```

Le tri par longueur décroissante est important : si un jour vous avez `03` et
`032`, un numéro `0324455667` doit être reconnu comme `032`, pas comme `03`.

### Où c'est utilisé

À la création et à la modification d'un compte, dans `Comptes::verifierPrefixe()` :
le numéro doit correspondre à un préfixe de l'opérateur choisi.

| Saisie | Résultat |
|---|---|
| `0329998887` + Telma | ❌ « Ce numéro appartient à Orange (préfixe 032). » |
| `0999998887` + Telma | ❌ « Le préfixe de ce numéro n'est enregistré pour aucun opérateur. » |
| `0389998887` + Telma | ✅ créé |

Cela empêche d'avoir un compte incohérent — ce qui fausserait ensuite tous les
calculs de commission et de compensation.

---

## 3. Le calcul de la commission

Dans `MouvementModel::transferer()` :

```php
// Frais fixe : tarif de l'opérateur de l'ÉMETTEUR, qui le garde.
$frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $sender['idOperateur']);

// Commission : % de l'opérateur du DESTINATAIRE, qui la garde.
$commission = $this->commissionPour($sender, $receiver, $montant);

// L'émetteur paie tout ; le destinataire reçoit le montant exact.
$total = $montant + $frais + $commission;
```

et la méthode privée :

```php
private function commissionPour(array $sender, array $receiver, float $montant): float
{
    if ((int) $sender['idOperateur'] === (int) $receiver['idOperateur']) {
        return 0.0;   // transfert interne : pas de commission
    }

    $operateurArrivee = model(OperateurModel::class)->find($receiver['idOperateur']);

    return $operateurArrivee === null
        ? 0.0
        : round($montant * (float) $operateurArrivee['pourcentageCommission'] / 100, 2);
}
```

Le `round(..., 2)` évite les montants à rallonge (1,5 % de 3 333 = 49,995).

Le contrôle de solde et le message d'erreur tiennent compte des deux :

```
Solde insuffisant (montant + frais de 150 Ar + commission de 150 Ar).
```

### Configurer le taux

Écran **Opérateurs** (`/operateurs`) : un taux par opérateur, modifiable.
La page affiche aussi, pour chaque opérateur, son nombre de préfixes, de
tarifs et de comptes — avec un ⚠ si sa grille tarifaire est vide (dans ce cas
tous ses frais vaudraient 0).

---

## 4. Point 3 — D'où viennent les gains ?

`MouvementModel::gainsDetailles($idOperateur)` décompose en trois sources :

| Source | Qui paie | Calcul |
|---|---|---|
| **Frais internes** | nos clients | frais des mouvements restés chez nous |
| **Frais sortants** | nos clients | frais des envois vers un autre opérateur |
| **Commissions reçues** | les autres opérateurs | commissions sur l'argent entrant |

La requête utilise des `SUM(CASE WHEN ...)` : une seule lecture de la table
produit les trois totaux, plutôt que trois requêtes séparées.

```sql
-- frais sur mouvements 100 % internes (dépôt/retrait, ou envoi entre nos clients)
COALESCE(SUM(CASE
  WHEN s.idOperateur = :op: AND (m.idReceiver IS NULL OR r.idOperateur = :op:)
  THEN m.frais ELSE 0 END), 0) AS fraisInternes,
```

Le `m.idReceiver IS NULL` couvre les **retraits** (pas de destinataire) : ils
sont bien de l'activité interne.

Affiché sur le dashboard sous « Situation des gains : chez nous / avec les
autres opérateurs », avec le total interne d'un côté et
`frais sortants + commissions reçues` de l'autre. La section n'apparaît que si
un opérateur précis est sélectionné — « tous les opérateurs » n'aurait pas de
sens ici, puisque « interne » se définit par rapport à *un* opérateur.

---

## 5. Point 4 — Ce qu'on doit à chaque opérateur

`MouvementModel::situationCompensation($idOperateur)` retourne une ligne par
autre opérateur :

| Colonne | Sens |
|---|---|
| `aVerser` | ce que nous lui devons = `SUM(montant + commission)` de nos envois vers lui |
| `aRecevoir` | ce qu'il nous doit = même calcul dans l'autre sens |
| `net` | `aVerser − aRecevoir` : positif = nous décaissons, négatif = nous encaissons |

**Les frais n'entrent jamais dans ce calcul** : ils restent chez l'opérateur
d'origine, ils ne sont pas dus.

Sur les données de test, pour Telma :

| Opérateur | À lui verser | Il nous doit | Net |
|---|---|---|---|
| Airtel | 35 875 | 12 240 | 23 635 à envoyer |
| Orange | 116 725 | 0 | 116 725 à envoyer |

La page `/compensation` affiche ce tableau, avec un total à verser, un total à
recevoir et un solde net. Un bouton « Détails » ouvre la liste des mouvements
échangés avec un opérateur donné, pour justifier le montant ligne par ligne.

---

## 6. Comment modifier / ajouter des choses

### Changer le taux de commission d'un opérateur

Écran **Opérateurs** → Modifier. Les mouvements passés ne bougent pas : la
commission y est figée. Seuls les nouveaux transferts utiliseront le nouveau
taux.

### Ajouter un opérateur

1. Écran **Opérateurs** → Ajouter (nom + taux de commission).
2. **Lui créer ses préfixes** (écran Préfixes) — sans quoi aucun compte ne
   pourra lui être rattaché, la validation du numéro échouera.
3. **Lui créer sa grille tarifaire** (écran Tarifs) — sans quoi tous ses frais
   vaudront 0.

Les trois étapes sont nécessaires : l'écran Opérateurs signale d'ailleurs par
un ⚠ les opérateurs sans tarifs.

### Faire varier la commission par tranche de montant

Aujourd'hui le taux est unique par opérateur (une colonne dans `operateurs`).
Pour le faire varier selon le montant, il faudrait une table `commissions`
bâtie comme `frais` (`idOperateur`, `minMontant`, `maxMontant`, `pourcentage`,
`dateCommission`) et remplacer la lecture de `pourcentageCommission` dans
`commissionPour()` par une recherche de tranche — exactement le même patron
que `FraisModel::fraisPour()`.

### Historiser les taux de commission

Même principe : ajouter une `dateCommission` et prendre la ligne la plus
récente antérieure à la date voulue. Le mouvement gardant sa commission figée,
l'historique resterait juste dans tous les cas.

---

## 7. Vérifications effectuées

| Test | Résultat |
|---|---|
| Compte `032…` rattaché à Telma | ✅ refusé, « Ce numéro appartient à Orange (préfixe 032). » |
| Préfixe inconnu `099…` | ✅ refusé |
| Compte `038…` rattaché à Telma | ✅ accepté |
| Transfert Telma → Orange, 10 000 | ✅ émetteur −10 300, destinataire +10 000 pile |
| — frais et commission en base | ✅ `frais=150`, `commission=150`, séparés |
| Transfert Telma → Telma, 10 000 | ✅ `commission=0` |
| Gains détaillés Telma | ✅ 1 350 / 1 500 / 240, identiques au SQL |
| Compensation Telma | ✅ Airtel 35 875 / 12 240, Orange 116 725 / 0, identiques au SQL |
| Intégrité SQLite + clés étrangères | ✅ ok, 0 violation |
| Logs d'erreur PHP | ✅ 0 |

Base restaurée depuis `base.sql` après les tests.
