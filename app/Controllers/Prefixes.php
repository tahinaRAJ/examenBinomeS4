<?php

namespace App\Controllers;

use App\Models\OperateurModel;
use App\Models\PrefixeModel;

class Prefixes extends BaseController
{
    public function index(): string
    {
        return view('prefixes/index', [
            'prefixes'   => model(PrefixeModel::class)->listeAvecOperateur(),
            'operateurs' => model(OperateurModel::class)->orderBy('nom')->findAll(),
        ]);
    }

    public function store()
    {
        $prefixes = model(PrefixeModel::class);

        if (! $prefixes->insert($this->request->getPost(['prefixe', 'idOperateur']))) {
            return redirect()->to('prefixes')->withInput()->with('errors', $prefixes->errors());
        }

        return redirect()->to('prefixes')->with('success', 'Préfixe ajouté.');
    }

    public function edit(int $id)
    {
        $prefixe = model(PrefixeModel::class)->find($id);

        if ($prefixe === null) {
            return redirect()->to('prefixes')->with('error', 'Préfixe introuvable.');
        }

        return view('prefixes/edit', [
            'prefixe'    => $prefixe,
            'operateurs' => model(OperateurModel::class)->orderBy('nom')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $prefixes = model(PrefixeModel::class);

        if ($prefixes->find($id) === null) {
            return redirect()->to('prefixes')->with('error', 'Préfixe introuvable.');
        }

        if (! $prefixes->update($id, $this->request->getPost(['prefixe', 'idOperateur']))) {
            return redirect()->back()->withInput()->with('errors', $prefixes->errors());
        }

        return redirect()->to('prefixes')->with('success', 'Préfixe modifié.');
    }

    public function delete(int $id)
    {
        model(PrefixeModel::class)->delete($id);

        return redirect()->to('prefixes')->with('success', 'Préfixe supprimé.');
    }
}
