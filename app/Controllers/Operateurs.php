<?php

namespace App\Controllers;

use App\Models\OperateurModel;

class Operateurs extends BaseController
{
    public function index(): string
    {
        return view('operateurs/index', [
            'operateurs' => model(OperateurModel::class)->listeAvecCompteurs(),
        ]);
    }

    public function store()
    {
        $operateurs = model(OperateurModel::class);

        if (! $operateurs->insert($this->donneesDuFormulaire())) {
            return redirect()->to('operateurs')->withInput()->with('errors', $operateurs->errors());
        }

        return redirect()->to('operateurs')->with('success', 'Opérateur ajouté.');
    }

    public function edit(int $id)
    {
        $operateur = model(OperateurModel::class)->find($id);

        if ($operateur === null) {
            return redirect()->to('operateurs')->with('error', 'Opérateur introuvable.');
        }

        return view('operateurs/edit', ['operateur' => $operateur]);
    }

    public function update(int $id)
    {
        $operateurs = model(OperateurModel::class);

        if ($operateurs->find($id) === null) {
            return redirect()->to('operateurs')->with('error', 'Opérateur introuvable.');
        }

        if (! $operateurs->update($id, $this->donneesDuFormulaire())) {
            return redirect()->back()->withInput()->with('errors', $operateurs->errors());
        }

        return redirect()->to('operateurs')->with('success', 'Opérateur modifié.');
    }

    private function donneesDuFormulaire(): array
    {
        $donnees = $this->request->getPost(['nom', 'pourcentageCommission']);

        if (($donnees['pourcentageCommission'] ?? '') === '') {
            $donnees['pourcentageCommission'] = 0;
        }

        return $donnees;
    }
}
