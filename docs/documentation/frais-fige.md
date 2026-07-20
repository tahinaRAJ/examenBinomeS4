# Gestion du changement des frais (schéma `base.sql`)

## Le besoin

Si la grille tarifaire change, les mouvements passés doivent garder le frais
qui était en vigueur à leur date. La solution est entièrement dans le schéma,
avec deux mécanismes complémentaires.

## 1. `mouvement.frais` — le frais appliqué, figé

La table `mouvement` a sa propre colonne `frais REAL NOT NULL DEFAULT 0` :
au moment d'enregistrer un mouvement, l'application calcule le frais depuis
la grille et **copie le résultat** dans cette colonne. On ne recalcule
jamais : pour l'historique et les rapports, on lit `mouvement.frais`.

## 2. `frais.dateFrais` — l'historique de la grille

La table `frais` a une colonne `dateFrais DATETIME NOT NULL DEFAULT
CURRENT_TIMESTAMP` : la **date d'entrée en vigueur** du tarif.

**Règle : on ne modifie jamais (UPDATE) et on ne supprime jamais une ligne de
`frais`.** Quand un tarif change, on **insère une nouvelle ligne** avec le
nouveau montant ; `dateFrais` prend automatiquement la date du jour.

Exemple — le frais de la tranche 0–10000 du type 1 passe de 100 à 250 :

```sql
-- On n'update PAS l'ancienne ligne, on ajoute la nouvelle :
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais)
VALUES (1, 0, 10000, 250);
```

## Trouver le tarif en vigueur à une date

Le tarif applicable est **la ligne la plus récente dont `dateFrais` est
antérieure ou égale à la date du mouvement**, pour le type et la tranche
concernés :

```sql
SELECT montantFrais
FROM frais
WHERE idTypeMouvement = :idType
  AND minMontant <= :montant
  AND maxMontant >= :montant
  AND dateFrais <= :dateMouvement      -- ou CURRENT_TIMESTAMP pour "maintenant"
ORDER BY dateFrais DESC
LIMIT 1;
```

- Pour un **nouveau** mouvement : exécuter cette requête avec la date
  actuelle, puis insérer le résultat dans `mouvement.frais`.
- Pour **vérifier/auditer** un mouvement passé : la même requête avec sa
  `dateMouvement` doit redonner la valeur figée dans `mouvement.frais`.

## Récapitulatif des règles

1. Créer un mouvement = calculer le frais via la requête ci-dessus, puis le
   figer dans `mouvement.frais`.
2. Changer un tarif = `INSERT` d'une nouvelle ligne dans `frais`, jamais
   d'`UPDATE`/`DELETE`.
3. Lire l'historique = `mouvement.frais`, jamais un recalcul par jointure sur
   la grille courante.
