<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\MouvementModel;

class ClientController extends BaseController
{
    // public function index(): string
    // {
    //     return view('clients/index', [
    //         'clients' => model(CompteModel::class)->findAll(),
    //     ]);
    // }

    public function profile()
    {
        $user = session()->get('user');

        if (! $user) {
            return redirect()->to('/login');
        }

        $client = model(CompteModel::class)->find($user['id']);

        if (! $client) {
            return redirect()->to('/login')->with('error', 'Client introuvable.');
        }

        return view('clients/profile', [
            'client' => $client,
        ]);
    }

    public function historique()
    {
        $user = session()->get('user');

        if (! $user) {
            return redirect()->to('/login');
        }

        $client = model(CompteModel::class)->find($user['id']);

        if (! $client) {
            return redirect()->to('/login')->with('error', 'Client introuvable.');
        }

        return view('clients/historique', [
            'client' => $client,
            'mouvements' => model(MouvementModel::class)->mouvementsDuCompte($user['id']),
        ]);
    }

}