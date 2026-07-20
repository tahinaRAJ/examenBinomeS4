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

    // ---------- Aperçu en direct (AJAX) ----------

    /**
     * Renvoie en JSON le détail chiffré d'une opération, sans l'effectuer.
     * Alimente l'aperçu affiché sous les formulaires.
     *
     * $operation : 'transfert' | 'retrait' | 'depot'
     */
    public function simuler(string $operation)
    {
        $client = $this->clientConnecte();

        if (! is_array($client)) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Non connecté.']);
        }

        $montant    = (float) $this->request->getPost('montant');
        $mouvements = model(MouvementModel::class);

        $resultat = match ($operation) {
            'transfert' => $mouvements->simulerTransfertMultiple(
                $client['id'],
                $this->numerosDestinataires(),
                $montant,
                $this->request->getPost('retraitInclus') !== null
            ),
            'retrait' => $mouvements->simulerRetrait($client['id'], $montant),
            'depot'   => $mouvements->simulerDepot($client['id'], $montant),
            default   => ['ok' => false, 'message' => 'Opération inconnue.'],
        };

        return $this->response->setJSON($resultat);
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

        $montant       = (float) $this->request->getPost('montant');
        $retraitInclus = $this->request->getPost('retraitInclus') !== null;
        $resultat      = model(MouvementModel::class)->transfererMultiple(
            $client['id'],
            $this->numerosDestinataires(),
            $montant,
            $retraitInclus
        );

        return redirect()->to('/client/transfert')
            ->with($resultat['success'] ? 'success' : 'erreur', $resultat['message']);
    }

    /**
     * Normalise les champs dynamiques du formulaire de transfert.
     */
    private function numerosDestinataires(): array
    {
        $numeros = $this->request->getPost('numerosReceiver');

        if (! is_array($numeros)) {
            $ancienChamp = $this->request->getPost('numeroReceiver');
            $numeros = $ancienChamp === null ? [] : [$ancienChamp];
        }

        return array_values(array_map(
            static fn ($numero): string => trim((string) $numero),
            $numeros
        ));
    }
}