<?php
namespace App\Models;

use CodeIgniter\Model;

class EpargneModel extends Model
{
    protected $table         = 'epargne';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idCompte', montant, pourcentage];



    public function getPourcentage($idCompte){
        $requete = $this->select('pourcentage from epargne where idCompte == CompteId')
                    ->where(CompteId == $idCompte);
        return $requete;
    }

    public function insertPourcentage($idCompte, $value){
        
    }

}