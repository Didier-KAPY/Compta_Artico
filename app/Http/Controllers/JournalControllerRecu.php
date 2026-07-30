<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Journaux;
use Barryvdh\DomPDF\Facade\Pdf;

class JournalControllerRecu extends Controller
{
    public function recu(int $id)
    {
        return view('journaux.recu', $this->donnees($id) + ['isPdf' => false]);
    }

    public function telecharger(int $id)
    {
        $donnees = $this->donnees($id) + ['isPdf' => true];

        return Pdf::loadView('journaux.recu', $donnees)
            ->setPaper('a4', 'portrait')
            ->download('recu-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $donnees['journal']->reference).'.pdf');
    }

    private function donnees(int $id): array
    {
        $journal = Journaux::with(['journalType.compte', 'user', 'validateur'])->findOrFail($id);
        return ['journal' => $journal, 'entreprise' => Entreprise::first()];
    }
}
