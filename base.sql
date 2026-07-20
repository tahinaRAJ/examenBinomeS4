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