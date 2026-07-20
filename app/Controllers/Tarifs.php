<?php

namespace App\Controllers;

use App\Models\FraisModel;
use App\Models\OperateurModel;
use App\Models\TypeMouvementModel;

class Tarifs extends BaseController
{
    public function index(): string
    {
        $idOperateur = $this->idOperateurFiltre();

        return view('tarifs/index', [
            'tarifs'      => model(FraisModel::class)->listeAvecType($idOperateur),
            'types'       => model(TypeMouvementModel::class)->typesTarifables(),
            'operateurs'  => model(OperateurModel::class)->orderBy('nom')->findAll(),
            'idOperateur' => $idOperateur,
        ]);
    }

    public function store()
    {
        $frais = model(FraisModel::class);

        if (! $frais->insert($this->donneesDuFormulaire())) {
            return redirect()->to('tarifs')->withInput()->with('errors', $frais->errors());
        }

        return redirect()->to('tarifs')->with('success', 'Tarif ajouté.');
    }

    public function edit(int $id)
    {
        $tarif = model(FraisModel::class)->find($id);

        if ($tarif === null) {
            return redirect()->to('tarifs')->with('error', 'Tarif introuvable.');
        }

        return view('tarifs/edit', [
            'tarif'      => $tarif,
            'types'      => model(TypeMouvementModel::class)->typesTarifables(),
            'operateurs' => model(OperateurModel::class)->orderBy('nom')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $frais = model(FraisModel::class);

        if ($frais->find($id) === null) {
            return redirect()->to('tarifs')->with('error', 'Tarif introuvable.');
        }

        if (! $frais->update($id, $this->donneesDuFormulaire())) {
            return redirect()->back()->withInput()->with('errors', $frais->errors());
        }

        return redirect()->to('tarifs')->with('success', 'Tarif modifié.');
    }

    public function delete(int $id)
    {
        model(FraisModel::class)->delete($id);

        return redirect()->to('tarifs')->with('success', 'Tarif supprimé.');
    }

    /**
     * Champs du formulaire tarif ; la date est optionnelle
     * (vide => date du jour, gérée par le DEFAULT de la colonne).
     */
    private function donneesDuFormulaire(): array
    {
        $donnees = $this->request->getPost(['idOperateur', 'idTypeMouvement', 'minMontant', 'maxMontant', 'montantFrais']);

        $date = $this->request->getPost('dateFrais');
        if ($date !== null && $date !== '') {
            // input datetime-local => "Y-m-d\TH:i"
            $donnees['dateFrais'] = str_replace('T', ' ', $date) . (strlen($date) === 16 ? ':00' : '');
        }

        return $donnees;
    }

    /**
     * Lit le filtre ?operateur=ID de la liste des tarifs.
     * Retourne null si absent/vide => "Tous les opérateurs".
     */
    private function idOperateurFiltre(): ?int
    {
        $valeur = $this->request->getGet('operateur');

        return ($valeur === null || $valeur === '') ? null : (int) $valeur;
    }
}
