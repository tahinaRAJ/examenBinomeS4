<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\MouvementModel;
use App\Models\OperateurModel;
use App\Models\PrefixeModel;

class Comptes extends BaseController
{
    public function index(): string
    {
        return view('comptes/index', [
            'comptes' => model(CompteModel::class)->listeAvecOperateur(),
            'stats'   => model(CompteModel::class)->stats(),
        ]);
    }

    public function show(int $id)
    {
        $compte = model(CompteModel::class)->detailAvecOperateur($id);

        if ($compte === null) {
            return redirect()->to('comptes')->with('error', 'Compte introuvable.');
        }

        return view('comptes/show', [
            'compte'     => $compte,
            'mouvements' => model(MouvementModel::class)->mouvementsDuCompte($id),
        ]);
    }

    public function create(): string
    {
        return view('comptes/form', [
            'compte'     => null,
            'operateurs' => model(OperateurModel::class)->orderBy('nom')->findAll(),
        ]);
    }

    public function store()
    {
        $comptes = model(CompteModel::class);
        $donnees = $this->donneesDuFormulaire();

        $erreurPrefixe = $this->verifierPrefixe($donnees);
        if ($erreurPrefixe !== null) {
            return redirect()->to('comptes/create')->withInput()->with('errors', [$erreurPrefixe]);
        }

        if (! $comptes->insert($donnees)) {
            return redirect()->to('comptes/create')->withInput()->with('errors', $comptes->errors());
        }

        return redirect()->to('comptes')->with('success', 'Compte créé.');
    }

    public function edit(int $id)
    {
        $compte = model(CompteModel::class)->find($id);

        if ($compte === null) {
            return redirect()->to('comptes')->with('error', 'Compte introuvable.');
        }

        return view('comptes/form', [
            'compte'     => $compte,
            'operateurs' => model(OperateurModel::class)->orderBy('nom')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $comptes = model(CompteModel::class);

        if ($comptes->find($id) === null) {
            return redirect()->to('comptes')->with('error', 'Compte introuvable.');
        }

        $donnees = $this->donneesDuFormulaire();

        $erreurPrefixe = $this->verifierPrefixe($donnees);
        if ($erreurPrefixe !== null) {
            return redirect()->back()->withInput()->with('errors', [$erreurPrefixe]);
        }

        if (! $comptes->update($id, $donnees)) {
            return redirect()->back()->withInput()->with('errors', $comptes->errors());
        }

        return redirect()->to('comptes/' . $id)->with('success', 'Compte modifié.');
    }

    /**
     * Active / désactive un compte depuis la liste.
     */
    public function toggle(int $id)
    {
        $comptes = model(CompteModel::class);
        $compte  = $comptes->find($id);

        if ($compte === null) {
            return redirect()->to('comptes')->with('error', 'Compte introuvable.');
        }

        $comptes->changerActivation($id, ! ((int) $compte['estActif'] === 1));

        return redirect()->back()->with('success', 'Compte ' . ((int) $compte['estActif'] === 1 ? 'désactivé' : 'activé') . '.');
    }

    /**
     * Le numéro doit correspondre à un préfixe de l'opérateur choisi
     * (un 032… est un numéro Orange, il ne peut pas être rattaché à Telma).
     * Retourne le message d'erreur, ou null si tout va bien.
     */
    private function verifierPrefixe(array $donnees): ?string
    {
        $numero      = (string) ($donnees['numero'] ?? '');
        $idOperateur = (int) ($donnees['idOperateur'] ?? 0);

        if ($numero === '' || $idOperateur === 0) {
            return null;   // laissé aux règles de validation du Model
        }

        $prefixes = model(PrefixeModel::class);

        if ($prefixes->numeroAppartientA($numero, $idOperateur)) {
            return null;
        }

        $trouve = $prefixes->operateurPourNumero($numero);

        return $trouve === null
            ? 'Le préfixe de ce numéro n\'est enregistré pour aucun opérateur.'
            : 'Ce numéro appartient à ' . $trouve['nomOperateur'] . ' (préfixe ' . $trouve['prefixe'] . ').';
    }

    private function donneesDuFormulaire(): array
    {
        $donnees = $this->request->getPost(['numero', 'nom', 'solde', 'idOperateur']);

        $donnees['solde']    = $donnees['solde'] === '' || $donnees['solde'] === null ? 0 : $donnees['solde'];
        $donnees['estActif'] = $this->request->getPost('estActif') !== null ? 1 : 0;

        return $donnees;
    }
}
