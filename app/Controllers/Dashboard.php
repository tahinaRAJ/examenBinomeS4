<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\MouvementModel;
use App\Models\OperateurModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $idOperateur = $this->idOperateurFiltre();
        $mouvements  = model(MouvementModel::class);

        return view('dashboard/index', [
            'operateurs'      => model(OperateurModel::class)->orderBy('nom')->findAll(),
            'idOperateur'     => $idOperateur,
            'statsMouvements' => $mouvements->stats($idOperateur),
            'gainsDuJour'     => $mouvements->gainsDuJour($idOperateur),
            'gainsParJour'    => $mouvements->gainsParJour($idOperateur),
            'statsParType'    => $mouvements->statsParType($idOperateur),
            'statsComptes'    => model(CompteModel::class)->stats($idOperateur),
            'comptes'         => model(CompteModel::class)->listeAvecOperateur($idOperateur),
        ]);
    }

    /**
     * Détail des gains d'une journée : chaque mouvement et son frais figé.
     * Le filtre opérateur du dashboard est conservé (paramètre ?operateur=).
     */
    public function gains(string $jour)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)) {
            return redirect()->to('dashboard')->with('error', 'Date invalide.');
        }

        $idOperateur = $this->idOperateurFiltre();

        return view('dashboard/gains', [
            'jour'        => $jour,
            'idOperateur' => $idOperateur,
            'operateurs'  => model(OperateurModel::class)->orderBy('nom')->findAll(),
            'mouvements'  => model(MouvementModel::class)->detailsDuJour($jour, $idOperateur),
        ]);
    }

    /**
     * Lit le filtre ?operateur=ID envoyé par le sélecteur du dashboard.
     * Retourne null si absent/vide => "Tous les opérateurs".
     */
    private function idOperateurFiltre(): ?int
    {
        $valeur = $this->request->getGet('operateur');

        return ($valeur === null || $valeur === '') ? null : (int) $valeur;
    }
}
