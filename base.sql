PRAGMA foreign_keys = ON;


-- OPERATEURS


CREATE TABLE operateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,

    -- % que CET opérateur prélève sur l'argent qui ENTRE chez lui
    -- depuis un autre opérateur (commission d'interconnexion).
    -- 2.0 = 2 %. Voir frais-fige.md.
    pourcentageCommission REAL NOT NULL DEFAULT 0
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

    -- chaque opérateur a sa propre grille tarifaire
    idOperateur INTEGER NOT NULL,

    idTypeMouvement INTEGER NOT NULL,

    minMontant REAL NOT NULL,

    maxMontant REAL NOT NULL,

    montantFrais REAL NOT NULL,

    dateFrais DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(idOperateur)
        REFERENCES operateurs(id),

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

    -- frais fixe, gardé par l'opérateur d'ORIGINE (celui de l'émetteur)
    frais REAL NOT NULL DEFAULT 0,

    -- commission %, gardée par l'opérateur d'ARRIVÉE (celui du destinataire),
    -- uniquement sur les transferts entre deux opérateurs différents.
    -- Figée comme les frais : jamais recalculée.
    commission REAL NOT NULL DEFAULT 0,

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
-- pourcentageCommission = % prélevé sur l'argent entrant depuis un autre opérateur
INSERT INTO operateurs (nom, pourcentageCommission) VALUES ('Telma',  2.0);   -- id 1 (notre opérateur)
INSERT INTO operateurs (nom, pourcentageCommission) VALUES ('Orange', 1.5);   -- id 2
INSERT INTO operateurs (nom, pourcentageCommission) VALUES ('Airtel', 2.5);   -- id 3

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

-- Grille tarifaire : PROPRE À CHAQUE OPÉRATEUR, et historisée par dateFrais.
-- Le tarif appliqué à un mouvement est celui de l'opérateur du compte qui
-- paie : le titulaire pour un retrait, l'émetteur pour un envoi.
-- Types : 2 = Retrait, 3 = Envoi (1 = Dépôt, non tarifable).

-- --- Opérateur 1 (Telma) : tarifs initiaux au 2026-06-01
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 3, 0,     10000,  100,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 3, 10001, 50000,  300,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 3, 50001, 200000, 800,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 2, 0,     10000,  200,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 2, 10001, 50000,  500,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 2, 50001, 200000, 1000, '2026-06-01 00:00:00');
-- Changement de tarif Telma au 2026-07-15 : l'envoi 0-10000 passe de 100 à 150
-- (les mouvements antérieurs gardent 100 grâce au frais figé dans mouvement.frais)
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (1, 3, 0, 10000, 150, '2026-07-15 00:00:00');

-- --- Opérateur 2 (Orange) : moins cher sur l'envoi, plus cher sur le retrait
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 3, 0,     10000,  80,   '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 3, 10001, 50000,  250,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 3, 50001, 200000, 700,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 2, 0,     10000,  250,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 2, 10001, 50000,  600,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (2, 2, 50001, 200000, 1200, '2026-06-01 00:00:00');

-- --- Opérateur 3 (Airtel) : tarif unique plus simple
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 3, 0,     10000,  120,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 3, 10001, 50000,  350,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 3, 50001, 200000, 900,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 2, 0,     10000,  150,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 2, 10001, 50000,  450,  '2026-06-01 00:00:00');
INSERT INTO frais (idOperateur, idTypeMouvement, minMontant, maxMontant, montantFrais, dateFrais) VALUES (3, 2, 50001, 200000, 950,  '2026-06-01 00:00:00');

-- Comptes clients (répartis sur plusieurs opérateurs, pour pouvoir filtrer)
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0341112233', 'Rakoto Jean',      150000, 1, 1, '2026-06-05 09:00:00', '2026-06-05 09:00:00'); -- Telma
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0324455667', 'Rasoa Marie',       82000, 1, 2, '2026-06-08 10:30:00', '2026-06-08 10:30:00'); -- Orange
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0337788990', 'Rabe Paul',         41000, 1, 3, '2026-06-12 14:00:00', '2026-06-12 14:00:00'); -- Airtel
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0341239876', 'Randria Hery',     230000, 1, 1, '2026-06-20 08:15:00', '2026-06-20 08:15:00'); -- Telma
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0379990011', 'Ravao Lala',        12000, 1, 2, '2026-07-01 16:45:00', '2026-07-01 16:45:00'); -- Orange
INSERT INTO comptes (numero, nom, solde, estActif, idOperateur, created_at, updated_at) VALUES ('0345556677', 'Rakotoson Naly',        0, 0, 1, '2026-07-03 11:20:00', '2026-07-10 09:00:00'); -- Telma

-- Mouvements. Deux valeurs figées à la date du mouvement :
--   frais      = tarif fixe de l'opérateur de l'ÉMETTEUR (il le garde)
--   commission = % de l'opérateur du DESTINATAIRE (il le garde),
--                uniquement si les deux opérateurs sont différents.
-- Comptes : 1,4,6 = Telma | 2,5 = Orange | 3 = Airtel
-- Commissions : Telma 2 % | Orange 1,5 % | Airtel 2,5 %

-- 2026-07-10
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (1, NULL, 1,    50000,  0,    0,    '2026-07-10 08:30:00'); -- dépôt Rakoto (Telma)
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 1,    2,    5000,   100,  75,   '2026-07-10 09:12:00'); -- Telma->Orange : frais Telma 100 (ancien tarif) + comm Orange 1,5% = 75
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (2, 2,    NULL, 20000,  600,  0,    '2026-07-10 11:40:00'); -- retrait Rasoa (Orange) : 10001-50000 = 600

-- 2026-07-12
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 4,    3,    30000,  300,  750,  '2026-07-12 10:05:00'); -- Telma->Airtel : frais Telma 300 + comm Airtel 2,5% = 750
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 2,    5,    8000,   80,   0,    '2026-07-12 15:22:00'); -- Orange->Orange (INTERNE) : frais Orange 80, pas de commission

-- 2026-07-16 (le nouveau tarif Telma envoi 0-10000 : 150 est entré en vigueur le 15)
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 1,    3,    5000,   150,  125,  '2026-07-16 09:47:00'); -- Telma->Airtel : frais Telma 150 (nouveau) + comm Airtel 2,5% = 125
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (1, NULL, 4,    100000, 0,    0,    '2026-07-16 13:10:00'); -- dépôt Randria (Telma)

-- 2026-07-18
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (2, 4,    NULL, 60000,  1000, 0,    '2026-07-18 10:00:00'); -- retrait Randria (Telma) : 50001-200000 = 1000
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 3,    1,    12000,  350,  240,  '2026-07-18 17:35:00'); -- Airtel->Telma : frais Airtel 350 + comm Telma 2% = 240

-- 2026-07-19
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (3, 4,    2,    100000, 800,  1500, '2026-07-19 08:55:00'); -- Telma->Orange : frais Telma 800 + comm Orange 1,5% = 1500
INSERT INTO mouvement (idTypeMouvement, idSender, idReceiver, montant, frais, commission, dateMouvement) VALUES (2, 1,    NULL, 5000,   200,  0,    '2026-07-19 14:20:00'); -- retrait Rakoto (Telma) : 0-10000 = 200
