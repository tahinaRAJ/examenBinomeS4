<?php

namespace App\Models;

use CodeIgniter\Model;

class MouvementModel extends Model
{
    protected $table         = 'mouvement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['idTypeMouvement', 'idSender', 'idReceiver', 'montant', 'frais', 'dateMouvement'];

    /**
     * Enregistre un mouvement en figeant le frais en vigueur à cet instant.
     *
     * Le frais est calculé depuis la grille (table frais) puis copié dans
     * mouvement.frais : si la grille change plus tard, l'historique garde
     * le frais appliqué à la date du mouvement.
     *
     * @param int|null $idSender   null si dépôt
     * @param int|null $idReceiver null si retrait
     *
     * @return int id du mouvement créé
     */
    public function enregistrer(int $idTypeMouvement, float $montant, ?int $idSender = null, ?int $idReceiver = null): int
    {
        $frais = model(FraisModel::class)->fraisPour($idTypeMouvement, $montant);

        $this->insert([
            'idTypeMouvement' => $idTypeMouvement,
            'idSender'        => $idSender,
            'idReceiver'      => $idReceiver,
            'montant'         => $montant,
            'frais'           => $frais,
        ]);

        return (int) $this->getInsertID();
    }
}
