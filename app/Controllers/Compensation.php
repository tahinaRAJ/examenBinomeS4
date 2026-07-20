<?php

namespace App\Controllers;

use App\Models\MouvementModel;
use App\Models\OperateurModel;

/**
 * Situation des montants à envoyer à chaque opérateur.
 *
 * Quand un client de A envoie de l'argent à un client de B, A a encaissé
 * l'argent mais c'est B qui a crédité son client : A doit donc reverser à B
 * le montant + la commission qui revient à B.
 */
class Compensation extends BaseController
{
    public function index(): string
    {
        $operateurs  = model(OperateurModel::class)->orderBy('nom')->findAll();
        $idOperateur = $this->idOperateurChoisi($operateurs);

        return view('compensation/index', [
            'operateurs'  => $operateurs,
            'idOperateur' => $idOperateur,
            'situation'   => model(MouvementModel::class)->situationCompensation($idOperateur),
        ]);
    }

    public function details(int $idAutre)
    {
        $operateurs  = model(OperateurModel::class)->orderBy('nom')->findAll();
        $idOperateur = $this->idOperateurChoisi($operateurs);

        $autre = model(OperateurModel::class)->find($idAutre);

        if ($autre === null || $idAutre === $idOperateur) {
            return redirect()->to('compensation')->with('error', 'Opérateur invalide.');
        }

        return view('compensation/details', [
            'operateur'  => model(OperateurModel::class)->find($idOperateur),
            'autre'      => $autre,
            'mouvements' => model(MouvementModel::class)->detailsCompensation($idOperateur, $idAutre),
        ]);
    }

    /**
     * L'opérateur dont on regarde la situation (?operateur=ID).
     * Par défaut le premier de la liste.
     */
    private function idOperateurChoisi(array $operateurs): int
    {
        $valeur = $this->request->getGet('operateur');

        if ($valeur !== null && $valeur !== '') {
            return (int) $valeur;
        }

        return $operateurs === [] ? 0 : (int) $operateurs[0]['id'];
    }
}
