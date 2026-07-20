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
            ->orderBy('operateurs.nom')
            ->orderBy('prefixes.prefixe')
            ->findAll();
    }

    /**
     * À quel opérateur appartient un numéro, d'après son préfixe ?
     * (ex. '0324455667' commence par '032' => Orange)
     *
     * C'est ce qui rend la configuration des préfixes utile : elle permet
     * de reconnaître l'opérateur de n'importe quel numéro, y compris ceux
     * des autres opérateurs.
     *
     * Retourne null si aucun préfixe connu ne correspond.
     */
    public function operateurPourNumero(string $numero): ?array
    {
        // Le préfixe le plus long d'abord, au cas où deux se chevauchent
        $prefixes = $this->select('prefixes.*, operateurs.nom AS nomOperateur')
            ->join('operateurs', 'operateurs.id = prefixes.idOperateur')
            ->orderBy('LENGTH(prefixes.prefixe)', 'DESC')
            ->findAll();

        foreach ($prefixes as $prefixe) {
            if (str_starts_with($numero, $prefixe['prefixe'])) {
                return $prefixe;
            }
        }

        return null;
    }

    /**
     * Le numéro correspond-il bien à un préfixe de l'opérateur donné ?
     * Sert à empêcher de créer un compte 032xxx (Orange) chez Telma.
     */
    public function numeroAppartientA(string $numero, int $idOperateur): bool
    {
        $prefixe = $this->operateurPourNumero($numero);

        return $prefixe !== null && (int) $prefixe['idOperateur'] === $idOperateur;
    }
}
