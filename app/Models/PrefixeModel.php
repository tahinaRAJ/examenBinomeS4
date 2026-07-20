<?php

namespace App\Models;

use CodeIgniter\Model;

class PrefixeModel extends Model
{
    protected $table         = 'prefixes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['prefixe', 'idOperateur'];

    protected $validationRules = [
        'prefixe'     => 'required|min_length[2]|max_length[5]',
        'idOperateur' => 'required|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'prefixe' => [
            'required'   => 'Le préfixe est obligatoire.',
            'min_length' => 'Le préfixe doit faire au moins 2 caractères.',
            'max_length' => 'Le préfixe ne doit pas dépasser 5 caractères.',
        ],
        'idOperateur' => [
            'required'           => 'L\'opérateur est obligatoire.',
            'is_natural_no_zero' => 'Opérateur invalide.',
        ],
    ];

    /**
     * Liste des préfixes avec le nom de l'opérateur.
     */
    public function listeAvecOperateur(): array
    {
        return $this->select('prefixes.*, operateurs.nom AS nomOperateur')
            ->join('operateurs', 'operateurs.id = prefixes.idOperateur')
            ->orderBy('prefixes.prefixe')
            ->findAll();
    }
}
