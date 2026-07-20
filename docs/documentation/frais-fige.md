# Les frais : grille par opérateur, datée, et frais figé

Trois mécanismes se combinent dans la gestion des frais. Ce document les
explique ensemble, car ils répondent à trois questions différentes.

| Question | Mécanisme |
|---|---|
| **Qui** fixe le tarif ? | `frais.idOperateur` — chaque opérateur a sa grille |
| **Depuis quand** s'applique-t-il ? | `frais.dateFrais` — grille historisée |
| **Combien** a réellement été payé ? | `mouvement.frais` — snapshot figé |

---

## 1. `frais.idOperateur` — une grille par opérateur

Chaque opérateur (Telma, Orange, Airtel…) fixe ses propres tarifs. La table
`frais` porte donc un `idOperateur` :

```sql
CREATE TABLE frais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    idOperateur INTEGER NOT NULL,      -- la grille appartient à un opérateur
    idTypeMouvement INTEGER NOT NULL,
    minMontant REAL NOT NULL,
    maxMontant REAL NOT NULL,
    montantFrais REAL NOT NULL,
    dateFrais DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(idOperateur)     REFERENCES operateurs(id),
    FOREIGN KEY(idTypeMouvement) REFERENCES typeMouvement(id)
);
```

Exemple des données de test, pour la tranche 0–10 000 Ar :

| Opérateur | Retrait | Envoi |
|---|---|---|
| Telma | 200 Ar | 150 Ar (depuis le 15/07, 100 avant) |
| Orange | 250 Ar | 80 Ar |
| Airtel | 150 Ar | 120 Ar |

### Quel opérateur s'applique ? La règle

> **Le tarif appliqué est celui de l'opérateur du compte qui paie les frais.**

| Opération | Opérateur retenu | Pourquoi |
|---|---|---|
| **Retrait** | celui du titulaire du compte | c'est lui qui est débité |
| **Envoi** | celui de l'**émetteur** | l'émetteur paie, le destinataire reçoit le montant exact |
| **Dépôt** | aucun | type non tarifable, frais = 0 |

Un transfert Telma → Orange applique donc le tarif **Telma**, même si le
destinataire est chez Orange. C'est logique : le frais est le gain de
l'opérateur sur **son propre client**.

Dans `MouvementModel` :

```php
// Retrait : opérateur du titulaire
$frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $compte['idOperateur']);

// Envoi : opérateur de l'ÉMETTEUR
$frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $sender['idOperateur']);
```

---

## 2. `frais.dateFrais` — l'historique de la grille

`dateFrais` est la **date d'entrée en vigueur** du tarif.

> **Règle : on ne modifie jamais (UPDATE) et on ne supprime jamais une ligne
> de `frais`.** Quand un tarif change, on **insère une nouvelle ligne** ;
> `dateFrais` prend la date du jour par défaut.

Exemple — Telma (opérateur 1) passe son envoi (type 3) 0–10 000 de 100 à 150 :

```sql
-- On n'update PAS l'ancienne ligne, on ajoute la nouvelle :
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais)
VALUES (1, 3, 0, 10000, 150, '2026-07-15 00:00:00');
```

Les deux lignes coexistent : l'ancienne (100, depuis le 01/06) et la
nouvelle (150, depuis le 15/07). La grille d'Orange et d'Airtel n'est pas
touchée.

---

## 3. `mouvement.frais` — le frais réellement appliqué, figé

La table `mouvement` a sa propre colonne `frais REAL NOT NULL DEFAULT 0` :
au moment d'enregistrer un mouvement, le frais calculé est **copié** dedans.
On ne le recalcule jamais — si la grille change demain, les mouvements passés
gardent le frais de leur époque.

---

## Trouver le tarif en vigueur

Le tarif applicable est **la ligne la plus récente** correspondant à
l'opérateur, au type et à la tranche, dont `dateFrais` est antérieure ou
égale à la date voulue :

```sql
SELECT montantFrais
FROM frais
WHERE idOperateur     = :idOperateur
  AND idTypeMouvement = :idType
  AND minMontant <= :montant
  AND maxMontant >= :montant
  AND dateFrais  <= :date          -- CURRENT_TIMESTAMP pour "maintenant"
ORDER BY dateFrais DESC
LIMIT 1;
```

C'est exactement ce que fait `FraisModel::fraisPour()` :

```php
public function fraisPour(int $idTypeMouvement, float $montant, int $idOperateur, ?string $date = null): float
```

`$idOperateur` est **obligatoire** : depuis que chaque opérateur a sa grille,
un frais sans opérateur n'aurait plus de sens.

- Pour un **nouveau** mouvement : appeler avec la date actuelle (par défaut),
  puis figer le résultat dans `mouvement.frais`.
- Pour **auditer** un mouvement passé : rappeler avec sa `dateMouvement` et
  l'opérateur du payeur doit redonner la valeur figée.

---

## Récapitulatif des règles

1. Créer un mouvement = calculer le frais avec l'opérateur du **payeur**,
   puis le figer dans `mouvement.frais`.
2. Changer un tarif = `INSERT` d'une nouvelle ligne (avec son `idOperateur`),
   jamais d'`UPDATE`/`DELETE`.
3. Lire l'historique = `mouvement.frais`, jamais un recalcul par jointure sur
   la grille courante.

## À savoir

`fraisPour()` retourne **0** si aucune tranche ne correspond — montant
au-dessus de la tranche maximale, ou opérateur n'ayant pas de grille pour ce
type. Pour éviter que les gros montants passent sans frais, ajouter une
tranche haute (ex. `200001` → `99999999`) à la grille de **chaque** opérateur.

## Ajouter un opérateur

Un nouvel opérateur n'a aucune grille tant qu'on ne lui en crée pas une :
tous ses frais vaudraient 0. Après l'`INSERT` dans `operateurs`, penser à
saisir ses tranches (écran **Tarifs**, ou `INSERT` dans `frais`).
