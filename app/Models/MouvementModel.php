<?php

namespace App\Models;

use CodeIgniter\Model;

class MouvementModel extends Model
{
    protected $table         = 'mouvement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idTypeMouvement', 'idSender', 'idReceiver', 'montant', 'frais', 'commission', 'dateMouvement'];

    /**
     * Restreint la requête en cours aux mouvements qui touchent un compte
     * de l'opérateur donné (émetteur OU destinataire), en joignant la table
     * comptes sous les alias "sender" / "receiver".
     *
     * $idOperateur = null => aucun filtre (tous les opérateurs).
     * À appeler AVANT get()/findAll(), juste après avoir démarré la requête.
     */
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

    /**
     * Statistiques globales (pour le dashboard).
     * Les gains de l'opérateur = somme des frais figés dans mouvement.frais.
     */
    public function stats(?int $idOperateur = null): array
    {
        $ligne = $this->select('COUNT(*) AS nbMouvements')
            ->select('COALESCE(SUM(mouvement.montant), 0) AS volumeTotal')
            ->select('COALESCE(SUM(mouvement.frais), 0) AS totalGains')
            ->filtrerParOperateur($idOperateur)
            ->get()->getRowArray();

        return [
            'nbMouvements' => (int) $ligne['nbMouvements'],
            'volumeTotal'  => (float) $ligne['volumeTotal'],
            'totalGains'   => (float) $ligne['totalGains'],
        ];
    }

    /**
     * Gains du jour courant.
     */
    public function gainsDuJour(?int $idOperateur = null): float
    {
        $ligne = $this->select('COALESCE(SUM(mouvement.frais), 0) AS gains')
            ->where('DATE(mouvement.dateMouvement)', date('Y-m-d'))
            ->filtrerParOperateur($idOperateur)
            ->get()->getRowArray();

        return (float) $ligne['gains'];
    }

    /**
     * Historique des gains par jour (du plus récent au plus ancien).
     */
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

    /**
     * Détail des mouvements d'une journée, avec type et comptes concernés.
     * On lit mouvement.frais (frais figé) : jamais de recalcul depuis la grille.
     */
    public function detailsDuJour(string $jour, ?int $idOperateur = null): array
    {
        $requete = $this->select('mouvement.*, typeMouvement.libelle AS libelleType')
            ->select('sender.numero AS numeroSender, receiver.numero AS numeroReceiver')
            ->join('typeMouvement', 'typeMouvement.id = mouvement.idTypeMouvement');

        // Les jointures vers comptes sont déjà faites par filtrerParOperateur()
        // quand un filtre est actif ; sinon on les ajoute nous-mêmes pour
        // pouvoir afficher les numéros.
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

    /**
     * Mouvements d'un compte (envoyés ou reçus), pour la page de détail.
     */
    public function mouvementsDuCompte(int $idCompte): array
    {
        return $this->select('mouvement.*, typeMouvement.libelle AS libelleType')
            ->select('sender.numero AS numeroSender, receiver.numero AS numeroReceiver')
            ->join('typeMouvement', 'typeMouvement.id = mouvement.idTypeMouvement')
            ->join('comptes AS sender', 'sender.id = mouvement.idSender', 'left')
            ->join('comptes AS receiver', 'receiver.id = mouvement.idReceiver', 'left')
            ->groupStart()
                ->where('mouvement.idSender', $idCompte)
                ->orWhere('mouvement.idReceiver', $idCompte)
            ->groupEnd()
            ->orderBy('mouvement.dateMouvement', 'DESC')
            ->findAll();
    }

    /**
     * Répartition des mouvements par type (pour le dashboard).
     */
    public function statsParType(?int $idOperateur = null): array
    {
        return $this->select('typeMouvement.libelle AS libelleType')
            ->select('COUNT(*) AS nb')
            ->select('COALESCE(SUM(mouvement.montant), 0) AS volume')
            ->select('COALESCE(SUM(mouvement.frais), 0) AS gains')
            ->join('typeMouvement', 'typeMouvement.id = mouvement.idTypeMouvement')
            ->filtrerParOperateur($idOperateur)
            ->groupBy('mouvement.idTypeMouvement')
            ->orderBy('gains', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Dépôt : crédite le compte, aucun frais. Opération atomique
     * (insertion du mouvement + mise à jour du solde dans une transaction).
     */
    public function deposer(int $idCompte, float $montant): array
    {
        if ($montant <= 0) {
            return ['success' => false, 'message' => 'Le montant doit être positif.'];
        }

        $comptes = model(CompteModel::class);
        $compte  = $comptes->find($idCompte);

        if ($compte === null) {
            return ['success' => false, 'message' => 'Compte introuvable.'];
        }

        $idType = model(TypeMouvementModel::class)->idParLibelle('Dépôt');

        $this->db->transStart();

        $this->insert([
            'idTypeMouvement' => $idType,
            'idSender'        => null,
            'idReceiver'      => $idCompte,
            'montant'         => $montant,
            'frais'           => 0,
        ]);

        $comptes->update($idCompte, ['solde' => $compte['solde'] + $montant]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Erreur lors du dépôt.'];
        }

        return ['success' => true, 'message' => 'Dépôt de ' . number_format($montant, 0, ',', ' ') . ' Ar effectué.'];
    }

    /**
     * Retrait : débite le compte du montant + frais (tarif en vigueur).
     * Refuse si le solde est insuffisant.
     */
    public function retirer(int $idCompte, float $montant): array
    {
        if ($montant <= 0) {
            return ['success' => false, 'message' => 'Le montant doit être positif.'];
        }

        $comptes = model(CompteModel::class);
        $compte  = $comptes->find($idCompte);

        if ($compte === null) {
            return ['success' => false, 'message' => 'Compte introuvable.'];
        }

        $idType = model(TypeMouvementModel::class)->idParLibelle('Retrait');
        // Tarif de l'opérateur du titulaire du compte : chaque opérateur a sa grille
        $frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $compte['idOperateur']);
        $total = $montant + $frais;

        if ($compte['solde'] < $total) {
            return ['success' => false, 'message' => 'Solde insuffisant (montant + frais de ' . number_format($frais, 0, ',', ' ') . ' Ar).'];
        }

        $this->db->transStart();

        $this->insert([
            'idTypeMouvement' => $idType,
            'idSender'        => $idCompte,
            'idReceiver'      => null,
            'montant'         => $montant,
            'frais'           => $frais,
        ]);

        $comptes->update($idCompte, ['solde' => $compte['solde'] - $total]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Erreur lors du retrait.'];
        }

        return ['success' => true, 'message' => 'Retrait de ' . number_format($montant, 0, ',', ' ') . ' Ar effectué.'];
    }

    /**
     * Transfert : débite l'émetteur du montant + frais, crédite le
     * destinataire (retrouvé par son numéro) du montant seul.
     */
    public function transferer(int $idSender, string $numeroReceiver, float $montant, bool $retraitInclus = false): array
    {
        if ($montant <= 0) {
            return ['success' => false, 'message' => 'Le montant doit être positif.'];
        }

        $comptes  = model(CompteModel::class);
        $sender   = $comptes->find($idSender);
        $receiver = $comptes->where('numero', $numeroReceiver)->first();

        if ($sender === null) {
            return ['success' => false, 'message' => 'Compte introuvable.'];
        }

        if ($receiver === null) {
            return ['success' => false, 'message' => 'Le numéro destinataire est introuvable.'];
        }

        if ((int) $receiver['id'] === $idSender) {
            return ['success' => false, 'message' => 'Impossible de transférer vers son propre compte.'];
        }

        $idType = model(TypeMouvementModel::class)->idParLibelle('Envoi');
        $typeMouvements = model(TypeMouvementModel::class);
        // Frais fixe : tarif de l'opérateur de l'ÉMETTEUR, qui le garde.
        $frais = model(FraisModel::class)->fraisPour($idType, $montant, (int) $sender['idOperateur']);

        $fraisModel = model(FraisModel::class);
        $fraisRetrait = 0.0;
        $montantRecu    = $montant;

        if ($retraitInclus) {
            $frais = $fraisModel->fraisPour($typeMouvements->idParLibelle('Envoi'), $montant);
            $montantRecu    = $montant + $fraisRetrait;
        }
        $fraisTotal = $frais + $fraisRetrait;
        
        // Commission : % de l'opérateur du DESTINATAIRE, qui la garde.
        // Uniquement si les deux opérateurs sont différents (pas sur un
        // transfert interne).
        $commission = $this->commissionPour($sender, $receiver, $montant);

        // L'émetteur paie tout : montant + frais + commission.
        // Le destinataire reçoit le montant exact.
        $total = $montant + $fraisTotal + $commission;

        if ($sender['solde'] < $total) {
            $detail = 'frais de ' . number_format($frais, 0, ',', ' ') . ' Ar';
            if ($commission > 0) {
                $detail .= ' + commission de ' . number_format($commission, 0, ',', ' ') . ' Ar';
            }

            return ['success' => false, 'message' => 'Solde insuffisant (montant + ' . $detail . ').'];
        }

        $this->db->transStart();

        $this->insert([
            'idTypeMouvement' => $idType,
            'idSender'        => $idSender,
            'idReceiver'      => $receiver['id'],
            'montant'         => $montantRecu,
            'frais'           => $frais,
            'commission'      => $commission,
        ]);

        $comptes->update($idSender, ['solde' => $sender['solde'] - $total]);
        $comptes->update($receiver['id'], ['solde' => $receiver['solde'] + $montant]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Erreur lors du transfert.'];
        }

        $message = 'Transfert de ' . number_format($montant, 0, ',', ' ') . ' Ar effectué vers ' . $numeroReceiver . '.';
        if ($commission > 0) {
            $message .= ' Commission inter-opérateurs : ' . number_format($commission, 0, ',', ' ') . ' Ar.';
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * Commission d'interconnexion sur un transfert.
     *
     * Elle n'existe que si l'émetteur et le destinataire sont chez deux
     * opérateurs différents. Le taux appliqué est celui de l'opérateur
     * d'ARRIVÉE, puisque c'est lui qui l'encaisse.
     */
    private function commissionPour(array $sender, array $receiver, float $montant): float
    {
        if ((int) $sender['idOperateur'] === (int) $receiver['idOperateur']) {
            return 0.0;   // transfert interne : pas de commission
        }

        $operateurArrivee = model(OperateurModel::class)->find($receiver['idOperateur']);

        if ($operateurArrivee === null) {
            return 0.0;
        }

        return round($montant * (float) $operateurArrivee['pourcentageCommission'] / 100, 2);
    }

    // ==================================================================
    // Situation des gains : interne / autres opérateurs
    // ==================================================================

    /**
     * Décompose les gains d'un opérateur en trois sources distinctes.
     *
     * Un opérateur gagne de deux façons :
     *   - les FRAIS qu'il prélève sur les mouvements de ses propres clients
     *     (en tant qu'opérateur d'origine) ;
     *   - les COMMISSIONS qu'il prélève sur l'argent qui entre chez lui
     *     depuis un autre opérateur (en tant qu'opérateur d'arrivée).
     *
     * On sépare en plus les frais internes (mouvement resté chez lui) des
     * frais sur envois sortants, comme demandé.
     */
    public function gainsDetailles(int $idOperateur): array
    {
        $sql = "
            SELECT
              -- frais sur mouvements 100 % internes (dépôt/retrait, ou envoi entre ses clients)
              COALESCE(SUM(CASE
                WHEN s.idOperateur = :op: AND (m.idReceiver IS NULL OR r.idOperateur = :op:)
                THEN m.frais ELSE 0 END), 0) AS fraisInternes,

              -- frais sur envois de ses clients vers un AUTRE opérateur
              COALESCE(SUM(CASE
                WHEN s.idOperateur = :op: AND r.idOperateur IS NOT NULL AND r.idOperateur <> :op:
                THEN m.frais ELSE 0 END), 0) AS fraisSortants,

              -- commissions encaissées sur l'argent entrant d'un autre opérateur
              COALESCE(SUM(CASE
                WHEN r.idOperateur = :op: AND s.idOperateur IS NOT NULL AND s.idOperateur <> :op:
                THEN m.commission ELSE 0 END), 0) AS commissionsRecues,

              -- pour information : commissions payées à d'autres opérateurs
              COALESCE(SUM(CASE
                WHEN s.idOperateur = :op: AND r.idOperateur IS NOT NULL AND r.idOperateur <> :op:
                THEN m.commission ELSE 0 END), 0) AS commissionsVersees
            FROM mouvement m
            LEFT JOIN comptes s ON s.id = m.idSender
            LEFT JOIN comptes r ON r.id = m.idReceiver
        ";

        $ligne = $this->db->query($sql, ['op' => $idOperateur])->getRowArray();

        $fraisInternes     = (float) $ligne['fraisInternes'];
        $fraisSortants     = (float) $ligne['fraisSortants'];
        $commissionsRecues = (float) $ligne['commissionsRecues'];

        return [
            'fraisInternes'      => $fraisInternes,
            'fraisSortants'      => $fraisSortants,
            'commissionsRecues'  => $commissionsRecues,
            'commissionsVersees' => (float) $ligne['commissionsVersees'],
            // ce que l'opérateur gagne vraiment
            'totalInterne'       => $fraisInternes,
            'totalAutres'        => $fraisSortants + $commissionsRecues,
            'total'              => $fraisInternes + $fraisSortants + $commissionsRecues,
        ];
    }

    // ==================================================================
    // Situation des montants à envoyer à chaque opérateur
    // ==================================================================

    /**
     * Ce que l'opérateur doit à chaque autre opérateur, et inversement.
     *
     * Quand un client de A envoie de l'argent à un client de B :
     *   - A a encaissé montant + frais + commission de son client ;
     *   - A garde ses frais ;
     *   - B doit créditer son client (montant) et encaisser sa commission.
     * Donc A doit reverser à B : montant + commission.
     *
     * Retourne une ligne par autre opérateur, avec le solde net.
     */
    public function situationCompensation(int $idOperateur): array
    {
        $sql = "
            SELECT
              autre.id  AS idAutre,
              autre.nom AS nomAutre,

              -- ce que NOUS devons à l'autre (nos clients ont envoyé chez lui)
              COALESCE(SUM(CASE WHEN s.idOperateur = :op: AND r.idOperateur = autre.id
                                THEN m.montant + m.commission ELSE 0 END), 0) AS aVerser,
              COALESCE(SUM(CASE WHEN s.idOperateur = :op: AND r.idOperateur = autre.id
                                THEN 1 ELSE 0 END), 0) AS nbEnvoyes,

              -- ce que l'autre nous doit (ses clients ont envoyé chez nous)
              COALESCE(SUM(CASE WHEN s.idOperateur = autre.id AND r.idOperateur = :op:
                                THEN m.montant + m.commission ELSE 0 END), 0) AS aRecevoir,
              COALESCE(SUM(CASE WHEN s.idOperateur = autre.id AND r.idOperateur = :op:
                                THEN 1 ELSE 0 END), 0) AS nbRecus
            FROM operateurs autre
            LEFT JOIN mouvement m ON 1 = 1
            LEFT JOIN comptes s ON s.id = m.idSender
            LEFT JOIN comptes r ON r.id = m.idReceiver
            WHERE autre.id <> :op:
            GROUP BY autre.id
            ORDER BY autre.nom
        ";

        $lignes = $this->db->query($sql, ['op' => $idOperateur])->getResultArray();

        return array_map(static function (array $l): array {
            $aVerser   = (float) $l['aVerser'];
            $aRecevoir = (float) $l['aRecevoir'];

            return [
                'idAutre'   => (int) $l['idAutre'],
                'nomAutre'  => $l['nomAutre'],
                'aVerser'   => $aVerser,
                'aRecevoir' => $aRecevoir,
                'nbEnvoyes' => (int) $l['nbEnvoyes'],
                'nbRecus'   => (int) $l['nbRecus'],
                // > 0 : nous devons de l'argent ; < 0 : on nous en doit
                'net'       => $aVerser - $aRecevoir,
            ];
        }, $lignes);
    }

    /**
     * Détail des mouvements échangés avec un autre opérateur
     * (les deux sens), pour justifier le montant de la compensation.
     */
    public function detailsCompensation(int $idOperateur, int $idAutre): array
    {
        return $this->select('mouvement.*, typeMouvement.libelle AS libelleType')
            ->select('sender.numero AS numeroSender, receiver.numero AS numeroReceiver')
            ->select('sender.idOperateur AS opSender, receiver.idOperateur AS opReceiver')
            ->join('typeMouvement', 'typeMouvement.id = mouvement.idTypeMouvement')
            ->join('comptes AS sender', 'sender.id = mouvement.idSender')
            ->join('comptes AS receiver', 'receiver.id = mouvement.idReceiver')
            ->groupStart()
                ->groupStart()
                    ->where('sender.idOperateur', $idOperateur)
                    ->where('receiver.idOperateur', $idAutre)
                ->groupEnd()
                ->orGroupStart()
                    ->where('sender.idOperateur', $idAutre)
                    ->where('receiver.idOperateur', $idOperateur)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('mouvement.dateMouvement', 'DESC')
            ->findAll();
    }
}