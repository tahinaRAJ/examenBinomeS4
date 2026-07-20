<?php

namespace App\Models;

use CodeIgniter\Model;

class MouvementModel extends Model
{
    protected $table         = 'mouvement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idTypeMouvement', 'idSender', 'idReceiver', 'montant', 'frais', 'dateMouvement'];

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
        $frais  = model(FraisModel::class)->fraisPour($idType, $montant);
        $total  = $montant + $frais;

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
    public function transferer(int $idSender, string $numeroReceiver, float $montant): array
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
        $frais  = model(FraisModel::class)->fraisPour($idType, $montant);
        $total  = $montant + $frais;

        if ($sender['solde'] < $total) {
            return ['success' => false, 'message' => 'Solde insuffisant (montant + frais de ' . number_format($frais, 0, ',', ' ') . ' Ar).'];
        }

        $this->db->transStart();

        $this->insert([
            'idTypeMouvement' => $idType,
            'idSender'        => $idSender,
            'idReceiver'      => $receiver['id'],
            'montant'         => $montant,
            'frais'           => $frais,
        ]);

        $comptes->update($idSender, ['solde' => $sender['solde'] - $total]);
        $comptes->update($receiver['id'], ['solde' => $receiver['solde'] + $montant]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Erreur lors du transfert.'];
        }

        return ['success' => true, 'message' => 'Transfert de ' . number_format($montant, 0, ',', ' ') . ' Ar effectué vers ' . $numeroReceiver . '.'];
    }
}