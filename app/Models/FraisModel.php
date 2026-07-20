<?php

namespace App\Models;

use CodeIgniter\Model;

class FraisModel extends Model
{
    protected $table         = 'frais';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idTypeMouvement', 'minMontant', 'maxMontant', 'montantFrais', 'dateFrais'];

    protected $validationRules = [
        'idTypeMouvement' => 'required|is_natural_no_zero',
        'minMontant'      => 'required|decimal|greater_than_equal_to[0]',
        'maxMontant'      => 'required|decimal|greater_than_equal_to[0]',
        'montantFrais'    => 'required|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'idTypeMouvement' => ['required' => 'Le type d\'opération est obligatoire.'],
        'minMontant'      => ['required' => 'Le montant minimum est obligatoire.', 'decimal' => 'Montant minimum invalide.'],
        'maxMontant'      => ['required' => 'Le montant maximum est obligatoire.', 'decimal' => 'Montant maximum invalide.'],
        'montantFrais'    => ['required' => 'Le montant du frais est obligatoire.', 'decimal' => 'Montant du frais invalide.'],
    ];

    /**
     * Liste des tarifs avec le libellé du type, du plus récent au plus ancien.
     */
    public function listeAvecType(): array
    {
        return $this->select('frais.*, typeMouvement.libelle AS libelleType')
            ->join('typeMouvement', 'typeMouvement.id = frais.idTypeMouvement')
            ->orderBy('frais.dateFrais', 'DESC')
            ->orderBy('frais.id', 'DESC')
            ->findAll();
    }

    /**
     * Tarif en vigueur pour un type et un montant à une date donnée :
     * la ligne la plus récente dont dateFrais <= date.
     */
    public function fraisPour(int $idTypeMouvement, float $montant, ?string $date = null): float
    {
        $date ??= date('Y-m-d H:i:s');

        $tranche = $this->where('idTypeMouvement', $idTypeMouvement)
            ->where('minMontant <=', $montant)
            ->where('maxMontant >=', $montant)
            ->where('dateFrais <=', $date)
            ->orderBy('dateFrais', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return $tranche !== null ? (float) $tranche['montantFrais'] : 0.0;
    }
}
