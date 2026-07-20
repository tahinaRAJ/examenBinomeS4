<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeMouvementModel extends Model
{
    protected $table         = 'typeMouvement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['libelle', 'estTarifable'];

    public function estTarifable(int $idTypeMouvement): bool
    {
        $type = $this->find($idTypeMouvement);

        return $type !== null && (int) $type['estTarifable'] === 1;
    }
}
