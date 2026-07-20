# Membres
- Ayman ETU004109
- Tahina ETU004547

# SETUP
- [x] création du projet CodeIgniter 
- [x] installation de la base SQLite
- [x] configuration de l'environnement

# Base de données
- [x] création de la base SQLite 
- [x] création des tables
- [x] insertion de données de test

# Template
- [x] adaptation du template

# Version1

## Backend

- [x] Création des modèles
- [x] Création des routes

### Partie Opérateur (Ayman)
- [x] CRUD des préfixes
- [x] Modifications des tariffs pour chaque type d'operation
- [x] Dashboard
    - [x] situation gain (somme des frais)
    - [x] listes des comptes avec une page détail
    - [x] filtre par opérateur (+ tous les opérateurs)

### Échanges entre opérateurs (Ayman)
- [x] Configuration des préfixes valable pour les autres opérateurs
    - [x] reconnaissance de l'opérateur d'un numéro par son préfixe
    - [x] contrôle à la création d'un compte (un 032… ne peut pas être Telma)
- [x] Configuration % de commission pour les transferts vers les autres opérateurs
    - [x] taux par opérateur (écran Opérateurs)
    - [x] commission prélevée en plus, au taux de l'opérateur d'arrivée
    - [x] commission figée dans le mouvement (colonne séparée des frais)
- [x] Situation gain : séparer opérateur et autres opérateurs
    - [x] frais internes / frais sortants / commissions reçues
- [x] Situation des montants à envoyer à chaque opérateur
    - [x] tableau de compensation (à verser / à recevoir / net)
    - [x] page de détail des mouvements échangés

### Partie Client (Tahina)
- [x] logique d'Authentification
- [x] logique de mouvement
- [x] création du controller
- [x] création des fonctions
         - [x] creation de deposer()
         - [x] creation de retirer()
         - [x] creation de transferer()
- [x] Information Client 
    - [x] Profil
    - [x] création des fonctions
         - [x] creation de getNom()
         - [x] creation de getSolde()
         - [x] creation de getNum()
    - [x] historique

### Vues
- [x] Vues opérateur (préfixes, tarifs, comptes, dashboard)
- [x] Vues client (connexion, profil, dépôt, retrait, transfert, historique)

### Documentation (docs/documentation/)
- [x] sqlite-setup.md — installation et configuration SQLite
- [x] frais-fige.md — grille datée et frais figé
- [x] application.md — architecture générale
- [x] partie1-cote-operateur.md — explication du code opérateur
- [x] partie2-cote-client.md — explication du code client
- [x] todo.md — étapes réalisées et durées
