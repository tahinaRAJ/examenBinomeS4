<?php

namespace App\Models;

use CodeIgniter\Model;

class OperateurModel extends Model
{
    protected $table         = 'operateurs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['nom', 'pourcentageCommission'];

    protected $validationRules = [
        'nom'                   => 'required|min_length[2]',
        'pourcentageCommission' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];

    protected $validationMessages = [
        'nom' => ['required' => 'Le nom de l\'opérateur est obligatoire.'],
        'pourcentageCommission' => [
            'decimal'                => 'Le pourcentage doit être un nombre (ex. 2 ou 1.5).',
            'greater_than_equal_to'  => 'Le pourcentage ne peut pas être négatif.',
            'less_than_equal_to'     => 'Le pourcentage ne peut pas dépasser 100.',
        ],
    ];

    /**
     * Opérateurs avec le nombre de comptes et de préfixes rattachés,
     * pour l'écran de configuration.
     */
    public function listeAvecCompteurs(): array
    {
        return $this->select('operateurs.*')
            ->select('(SELECT COUNT(*) FROM comptes WHERE comptes.idOperateur = operateurs.id) AS nbComptes')
            ->select('(SELECT COUNT(*) FROM prefixes WHERE prefixes.idOperateur = operateurs.id) AS nbPrefixes')
            ->select('(SELECT COUNT(*) FROM frais WHERE frais.idOperateur = operateurs.id) AS nbTarifs')
            ->orderBy('operateurs.nom')
            ->findAll();
    }

    /**
     * Taux de commission d'un opérateur (0 si introuvable).
     */
    public function pourcentageCommission(int $id): float
    {
        $operateur = $this->find($id);

        return $operateur === null ? 0.0 : (float) $operateur['pourcentageCommission'];
    }
}
