<?php

namespace App\Models;

use CodeIgniter\Model;

class CompteModel extends Model
{
    protected $table         = 'comptes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['numero', 'nom', 'solde', 'estActif', 'idOperateur'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'numero'      => 'required|min_length[10]|max_length[10]',
        'nom'         => 'required|min_length[2]',
        'solde'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'idOperateur' => 'required|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'numero' => [
            'required'   => 'Le numéro est obligatoire.',
            'min_length' => 'Le numéro doit faire 10 chiffres.',
            'max_length' => 'Le numéro doit faire 10 chiffres.',
        ],
        'nom'         => ['required' => 'Le nom est obligatoire.'],
        'solde'       => ['decimal' => 'Solde invalide.'],
        'idOperateur' => ['required' => 'L\'opérateur est obligatoire.'],
    ];

    /**
     * Liste des comptes avec le nom de l'opérateur.
     * $idOperateur permet de ne lister que les comptes d'un opérateur donné
     * (null = tous les opérateurs).
     */
    public function listeAvecOperateur(?int $idOperateur = null): array
    {
        $requete = $this->select('comptes.*, operateurs.nom AS nomOperateur')
            ->join('operateurs', 'operateurs.id = comptes.idOperateur');

        if ($idOperateur !== null) {
            $requete->where('comptes.idOperateur', $idOperateur);
        }

        return $requete->orderBy('comptes.nom')->findAll();
    }

    /**
     * Un compte avec le nom de son opérateur, ou null.
     */
    public function detailAvecOperateur(int $id): ?array
    {
        return $this->select('comptes.*, operateurs.nom AS nomOperateur')
            ->join('operateurs', 'operateurs.id = comptes.idOperateur')
            ->find($id);
    }

    /**
     * Statistiques globales sur les comptes (pour le dashboard).
     * $idOperateur permet de restreindre les stats à un seul opérateur.
     */
    public function stats(?int $idOperateur = null): array
    {
        $requete = $this->select('COUNT(*) AS total')
            ->select('SUM(estActif = 1) AS actifs')
            ->select('SUM(estActif = 0) AS inactifs')
            ->select('COALESCE(SUM(solde), 0) AS soldeTotal');

        if ($idOperateur !== null) {
            $requete->where('idOperateur', $idOperateur);
        }

        $ligne = $requete->get()->getRowArray();

        return [
            'total'      => (int) $ligne['total'],
            'actifs'     => (int) $ligne['actifs'],
            'inactifs'   => (int) $ligne['inactifs'],
            'soldeTotal' => (float) $ligne['soldeTotal'],
        ];
    }

    /**
     * Active / désactive un compte.
     */
    public function changerActivation(int $id, bool $actif): bool
    {
        return $this->update($id, ['estActif' => $actif ? 1 : 0]);
    }
}
