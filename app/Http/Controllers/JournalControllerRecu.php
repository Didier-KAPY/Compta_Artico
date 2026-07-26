<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JournalControllerRecu extends Controller
{
    public function recu($id)
{

    $journal = Journaux::with([
        'journalType',
        'user'
    ])->findOrFail($id);



    if($journal->statut != 'valide')
    {

        return back()
        ->with(
            'error',
            'Ce journal n’est pas validé.'
        );

    }



    $entreprise = auth()->user()
        ->entreprise;



    return view(
        'journaux.recu',
        compact(
            'journal',
            'entreprise'
        )
    );

}
}
