<?php

namespace App\Models;

use CodeIgniter\Model;

class FraisModel extends Model
{
    protected $table         = 'frais';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idOperateur', 'idTypeMouvement', 'minMontant', 'maxMontant', 'montantFrais', 'dateFrais'];

    protected $validationRules = [
        'idOperateur'     => 'required|is_natural_no_zero',
        'idTypeMouvement' => 'required|is_natural_no_zero',
        'minMontant'      => 'required|decimal|greater_than_equal_to[0]',
        'maxMontant'      => 'required|decimal|greater_than_equal_to[0]',
        'montantFrais'    => 'required|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'idOperateur'     => ['required' => 'L\'opérateur est obligatoire.'],
        'idTypeMouvement' => ['required' => 'Le type d\'opération est obligatoire.'],
        'minMontant'      => ['required' => 'Le montant minimum est obligatoire.', 'decimal' => 'Montant minimum invalide.'],
        'maxMontant'      => ['required' => 'Le montant maximum est obligatoire.', 'decimal' => 'Montant maximum invalide.'],
        'montantFrais'    => ['required' => 'Le montant du frais est obligatoire.', 'decimal' => 'Montant du frais invalide.'],
    ];

    /**
     * Liste des tarifs avec le libellé du type et le nom de l'opérateur.
     * $idOperateur permet de n'afficher que la grille d'un opérateur
     * (null = tous les opérateurs).
     */
    public function listeAvecType(?int $idOperateur = null): array
    {
        $requete = $this->select('frais.*, typeMouvement.libelle AS libelleType, operateurs.nom AS nomOperateur')
            ->join('typeMouvement', 'typeMouvement.id = frais.idTypeMouvement')
            ->join('operateurs', 'operateurs.id = frais.idOperateur');

        if ($idOperateur !== null) {
            $requete->where('frais.idOperateur', $idOperateur);
        }

        return $requete->orderBy('operateurs.nom')
            ->orderBy('frais.dateFrais', 'DESC')
            ->orderBy('frais.id', 'DESC')
            ->findAll();
    }

    /**
     * Tarif en vigueur pour un opérateur, un type et un montant à une date
     * donnée : la ligne la plus récente dont dateFrais <= date.
     *
     * Chaque opérateur ayant sa propre grille, $idOperateur est obligatoire :
     * c'est l'opérateur du compte qui paie les frais (le titulaire pour un
     * retrait, l'émetteur pour un envoi).
     */
    public function fraisPour(int $idTypeMouvement, float $montant, int $idOperateur, ?string $date = null): float
    {
        $operateurModel = model(OperateurModel::class);
        $promotion = $operateurModel->getPromotionPour($idOperateur) / 100;
        $date ??= date('Y-m-d H:i:s');

        $tranche = $this->where('idOperateur', $idOperateur)
            ->where('idTypeMouvement', $idTypeMouvement)
            ->where('minMontant <=', $montant)
            ->where('maxMontant >=', $montant)
            ->where('dateFrais <=', $date)
            ->orderBy('dateFrais', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return $tranche !== null ? (float) $tranche['montantFrais'] * $promotion : 0.0;
    }
}
