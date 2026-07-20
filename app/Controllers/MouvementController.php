<?php

namespace App\Controllers;

use App\Models\CompteModel;
use App\Models\MouvementModel;

class MouvementController extends BaseController
{
    private function clientConnecte()
    {
        $user = session()->get('user');

        if (! $user) {
            return redirect()->to('/login');
        }

        $client = model(CompteModel::class)->find($user['id']);

        if (! $client) {
            return redirect()->to('/login')->with('erreur', 'Client introuvable.');
        }

        return $client;
    }

     // ---------- Dépôt ----------

    public function depotForm()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        return view('clients/depot', ['client' => $client]);
    }

    public function depot()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        $montant  = (float) $this->request->getPost('montant');
        $resultat = model(MouvementModel::class)->deposer($client['id'], $montant);

        return redirect()->to('/client/depot')
            ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
    }

    // ---------- Retrait ----------

    public function retraitForm()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        return view('clients/retrait', ['client' => $client]);
    }

    public function retrait()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        $montant  = (float) $this->request->getPost('montant');
        $resultat = model(MouvementModel::class)->retirer($client['id'], $montant);

        return redirect()->to('/client/retrait')
            ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
    }

    // ---------- Transfert ----------

    public function transfertForm()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        return view('clients/transfert', ['client' => $client]);
    }

    public function transfert()
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $client;
        }

        $montant        = (float) $this->request->getPost('montant');
        $numeroReceiver = trim((string) $this->request->getPost('numeroReceiver'));
        $retraitInclus  = $this->request->getPost('retraitInclus') !== null;
        $resultat       = model(MouvementModel::class)->transferer($client['id'], $numeroReceiver, $montant, $retraitInclus
        );

        return redirect()->to('/client/transfert')
            ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
    }
}