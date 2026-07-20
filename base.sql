PRAGMA foreign_keys = ON;


-- OPERATEURS


CREATE TABLE operateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL
);


-- PREFIXES


CREATE TABLE prefixes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prefixe TEXT NOT NULL UNIQUE,
    idOperateur INTEGER NOT NULL,

    FOREIGN KEY(idOperateur)
        REFERENCES operateurs(id)
);


-- COMPTES


CREATE TABLE comptes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    numero TEXT NOT NULL UNIQUE,
    nom TEXT NOT NULL,

    solde REAL NOT NULL DEFAULT 0,

    estActif INTEGER NOT NULL DEFAULT 1,

    idOperateur INTEGER NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(idOperateur)
        REFERENCES operateurs(id)
);


-- TYPE MOUVEMENT


CREATE TABLE typeMouvement (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    libelle TEXT NOT NULL,

    estTarifable INTEGER NOT NULL DEFAULT 0
);



-- FRAIS


CREATE TABLE frais (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    idTypeMouvement INTEGER NOT NULL,

    minMontant REAL NOT NULL,

    maxMontant REAL NOT NULL,

    montantFrais REAL NOT NULL,

    dateFrais DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(idTypeMouvement)
        REFERENCES typeMouvement(id)
);


-- MOUVEMENT


CREATE TABLE mouvement (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    idTypeMouvement INTEGER NOT NULL,

    idSender INTEGER NULL,

    idReceiver INTEGER NULL,

    montant REAL NOT NULL,

    frais REAL NOT NULL DEFAULT 0,

    dateMouvement DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(idTypeMouvement)
        REFERENCES typeMouvement(id),

    FOREIGN KEY(idSender)
        REFERENCES comptes(id),

    FOREIGN KEY(idReceiver)
        REFERENCES comptes(id)
);


-- ============ DONNÉES DE TEST ============

-- Opérateurs
INSERT INTO operateurs (nom) VALUES ('Telma');   -- id 1 (notre opérateur)
INSERT INTO operateurs (nom) VALUES ('Orange');  -- id 2
INSERT INTO operateurs (nom) VALUES ('Airtel');  -- id 3

-- Préfixes
INSERT INTO prefixes (prefixe, idOperateur) VALUES ('034', 1);
INSERT INTO prefixes (prefixe, idOperateur) VALUES ('038', 1);
INSERT INTO prefixes (prefixe, idOperateur) VALUES ('032', 2);
INSERT INTO prefixes (prefixe, idOperateur) VALUES ('037', 2);
INSERT INTO prefixes (prefixe, idOperateur) VALUES ('033', 3);

-- Types de mouvement
INSERT INTO typeMouvement (libelle, estTarifable) VALUES ('Dépôt', 0);    -- id 1
INSERT INTO typeMouvement (libelle, estTarifable) VALUES ('Retrait', 1);  -- id 2
INSERT INTO typeMouvement (libelle, estTarifable) VALUES ('Envoi', 1);    -- id 3

-- Grille tarifaire (historisée par dateFrais)
-- Tarifs initiaux au 2026-06-01
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 0,      10000,  100,  '2026-06-01 00:00:00');
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 10001,  50000,  300,  '2026-06-01 00:00:00');
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 50001,  200000, 800,  '2026-06-01 00:00:00');
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 0,      10000,  200,  '2026-06-01 00:00:00');
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 10001,  50000,  500,  '2026-06-01 00:00:00');
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 50001,  200000, 1000, '2026-06-01 00:00:00');
-- Changement de tarif au 2026-07-15 : l'envoi 0-10000 passe de 100 à 150
-- (les mouvements antérieurs gardent 100 grâce au frais figé dans mouvement.frais)
INSERT INTO frais (idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 0, 10000, 150, '2026-07-15 00:00:00');

-- Comptes clients (répartis sur plusieurs opérateurs, pour pouvoir filtrer)
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0341112233', 'Rakoto Jean',      150000, 1, 1, '2026-06-05 09:00:00', '2026-06-05 09:00:00'); -- Telma
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0324455667', 'Rasoa Marie',       82000, 1, 2, '2026-06-08 10:30:00', '2026-06-08 10:30:00'); -- Orange
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0337788990', 'Rabe Paul',         41000, 1, 3, '2026-06-12 14:00:00', '2026-06-12 14:00:00'); -- Airtel
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0341239876', 'Randria Hery',     230000, 1, 1, '2026-06-20 08:15:00', '2026-06-20 08:15:00'); -- Telma
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0379990011', 'Ravao Lala',        12000, 1, 2, '2026-07-01 16:45:00', '2026-07-01 16:45:00'); -- Orange
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0345556677', 'Rakotoson Naly',        0, 0, 1, '2026-07-03 11:20:00', '2026-07-10 09:00:00'); -- Telma

-- Mouvements (frais figé = tarif en vigueur à la date du mouvement)
-- 2026-07-10 (tarif envoi 0-10000 : 100)
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (1, NULL, 1, 50000,  0,    '2026-07-10 08:30:00'); -- dépôt Rakoto
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 1,    2, 5000,   100,  '2026-07-10 09:12:00'); -- envoi (ancien tarif)
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (2, 2,    NULL, 20000, 500, '2026-07-10 11:40:00'); -- retrait

-- 2026-07-12
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 4,    3, 30000,  300,  '2026-07-12 10:05:00');
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 2,    5, 8000,   100,  '2026-07-12 15:22:00');

-- 2026-07-16 (le nouveau tarif envoi 0-10000 : 150 est entré en vigueur le 15)
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 1,    3, 5000,   150,  '2026-07-16 09:47:00');
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (1, NULL, 4, 100000, 0,    '2026-07-16 13:10:00');

-- 2026-07-18
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (2, 4,    NULL, 60000, 1000, '2026-07-18 10:00:00');
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 3,    1, 12000,  300,  '2026-07-18 17:35:00');

-- 2026-07-19
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (3, 4,    2, 100000, 800,  '2026-07-19 08:55:00');
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, dateMouvement) VALUES (2, 1,    NULL, 5000,  200, '2026-07-19 14:20:00');
