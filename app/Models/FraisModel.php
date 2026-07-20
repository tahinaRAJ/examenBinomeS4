<?php

namespace App\Models;

use CodeIgniter\Model;

class FraisModel extends Model
{
    protected $table         = 'frais';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idTypeMouvement', 'minMontant', 'maxMontant', 'montantFrais'];

    /**
     * Frais en vigueur AUJOURD'HUI pour un type de mouvement et un montant.
     *
     * Ne sert qu'au moment d'enregistrer un mouvement : le résultat est
     * figé dans mouvement.frais. Ne jamais l'utiliser pour recalculer
     * les frais d'un mouvement passé.
     */
    public function fraisPour(int $idTypeMouvement, float $montant): float
    {
        if (! model(TypeMouvementModel::class)->estTarifable($idTypeMouvement)) {
            return 0.0;
        }

        $tranche = $this->where('idTypeMouvement', $idTypeMouvement)
            ->where('minMontant <=', $montant)
            ->where('maxMontant >=', $montant)
            ->first();

        return $tranche !== null ? (float) $tranche['montantFrais'] : 0.0;
    }
}
