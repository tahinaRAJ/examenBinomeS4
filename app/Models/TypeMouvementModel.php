<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeMouvementModel extends Model
{
    protected $table         = 'typeMouvement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['libelle', 'estTarifable'];

    /**
     * Types soumis à frais uniquement (pour l'écran des tarifs).
     */
    public function typesTarifables(): array
    {
        return $this->where('estTarifable', 1)->orderBy('libelle')->findAll();
    }
}
