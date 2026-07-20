<?php

namespace App\Controllers;

use App\Models\CompteModel;

class AuthController extends BaseController
{
    public function showLoginForm()
    {
        if (session()->get('user')) {
            return redirect()->to('/profil');
        }

        return view('auth/login');
    }

    public function login()
    {
        $numero = trim((string) $this->request->getPost('numero'));

        if ($numero === '') {
            return redirect()->back()->withInput()->with('erreur', 'Veuillez renseigner le numéro.');
        }

        $user = model(CompteModel::class)->where('numero', $numero)->first();

        if (!$user) {
            return redirect()->back()->withInput()->with('erreur', 'Numéro introuvable.');
        }

        if (array_key_exists('estActif', $user) && (int) $user['estActif'] !== 1) {
            return redirect()->back()->withInput()->with('erreur', 'Compte inactif.');
        }

        session()->set('user', [
            'id'     => $user['id'],
            'nom'    => $user['nom'],
            'numero' => $user['numero'],
        ]);

        return redirect()->to('/profil');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/');
    }
}