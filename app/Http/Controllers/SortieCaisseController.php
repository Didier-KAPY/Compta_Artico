<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SortieCaisse;
use App\Models\JournalType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\WorkflowComptableService;
use App\Models\Journaux;
use Carbon\Carbon;

class SortieCaisseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
{
    $query = $this->sortiesFiltrees($request);

    $sorties = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('sortie_caisses.index', compact('sorties'));
}

    private function sortiesFiltrees(Request $request)
    {
        return SortieCaisse::with(['etatBesoin', 'user'])
            ->when($request->filled('numero'), fn ($query) => $query->where('numero', 'like', '%'.$request->numero.'%'))
            ->when($request->filled('date_debut'), fn ($query) => $query->whereDate('date', '>=', $request->date_debut))
            ->when($request->filled('date_fin'), fn ($query) => $query->whereDate('date', '<=', $request->date_fin))
            ->when(!$request->filled('numero') && !$request->filled('date_debut') && !$request->filled('date_fin'),
                fn ($query) => $query->whereDate('date', Carbon::today()));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sortie_caisses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'beneficiaire' => 'required|string|max:255',
            'motif' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'monnaie' => 'required|in:CDF,USD',
            'type' => 'required|in:Caisse,Banque,Mobile Money',
            'observation' => 'required|string',
        ]);

        SortieCaisse::create([
            'numero' => 'BS-' . time(),
            'date' => $request->date,
            'beneficiaire' => $request->beneficiaire,
            'motif' => $request->motif,
            'montant' => $request->montant,
            'monnaie' => $request->monnaie,
            'type' => $request->type,
            'observation' => $request->observation,
            'statut' => 'En attente',
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('sortie-caisses.index')
            ->with('success', 'Bon de sortie créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
 public function show($id)
{
    $sortie = SortieCaisse::with([
        'user',
        'etatBesoin.lignes'
    ])->findOrFail($id);



    $roleObservation = strtolower(
        auth()->user()
        ->role
        ?->observation ?? ''
    );



    $journalTypes = collect();



   $journalTypes = collect();


    if(
        str_contains($roleObservation,'caisse') ||
        str_contains($roleObservation,'banque') ||
        str_contains($roleObservation,'monnaie electronique') ||
        str_contains($roleObservation,'mobile money')
    ) {


        $journalTypes = JournalType::where('est_tresorerie', true)
            ->get();


    } else {


        $journalTypes = JournalType::where('est_tresorerie', true)
            ->get();

    }

    
    return view(
        'sortie_caisses.show',
        compact(
            'sortie',
            'journalTypes'
        )
    );
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sortie = SortieCaisse::findOrFail($id);

        if ($sortie->statut === 'Validé') {
            return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
        }

        return view('sortie_caisses.edit', compact('sortie'));
    }

    private function generateNumero()
{
    $annee = date('y');
    $mois = date('m');

    $base = $annee . $mois; // Exemple : 2606

    $last = SortieCaisse::where('numero', 'like', $base . '%')
        ->orderByDesc('id')
        ->first();

    $next = 1;

    if ($last) {
        // Exemple : 26060025
        $lastNumber = intval(substr($last->numero, 4));

        $next = $lastNumber + 1;
    }

    return $base . str_pad($next, 4, '0', STR_PAD_LEFT);
}
    /**
     * Update the specified resource in storage.
     */
 public function update(Request $request, string $id)
{
    $request->validate([
        'date' => 'required|date',
        'beneficiaire' => 'required|string|max:255',
        'motif' => 'required|string',
        'montant' => 'required|numeric|min:0',
        'monnaie' => 'required|in:CDF,USD',
        'type' => 'required|in:Caisse,Banque,Mobile Money',
        'observation' => 'required|string',
    ]);

    $sortie = SortieCaisse::findOrFail($id);

    if ($sortie->statut === 'Validé') {
        return back()->with('error', 'Ce Bon validé doit d’abord être réouvert.');
    }

    // Générer un numéro seulement si absent
    if (empty($sortie->numero)) {
        $sortie->numero = $this->generateNumero();
    }

    $sortie->date = $request->date;
    $sortie->beneficiaire = $request->beneficiaire;
    $sortie->motif = $request->motif;
    $sortie->montant = $request->montant;
    $sortie->monnaie = $request->monnaie;
    $sortie->type = $request->type;
    $sortie->observation = $request->observation;

    $sortie->save();

    return redirect()
        ->route('sortie-caisses.show', $sortie->id)
        ->with('success', 'Bon de sortie modifié avec succès.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $sortie = SortieCaisse::lockForUpdate()->findOrFail($id);
            if ($sortie->statut === 'Validé') {
                throw ValidationException::withMessages(['statut' => 'Un Bon validé doit d’abord être réouvert.']);
            }
            if ($sortie->journaux()->exists()) {
                throw ValidationException::withMessages(['dependance' => 'Suppression impossible : un Journal est lié.']);
            }
            $sortie->delete();
        });

        return redirect()->route('sortie-caisses.index')
            ->with('success', 'Bon de sortie supprimé avec succès.');
    }

public function valider(Request $request, $id)
{
    


    DB::beginTransaction();

    try {


        $sortie = SortieCaisse::findOrFail($id);



        // Générer le numéro si absent
        if(empty($sortie->numero)) {

            $sortie->numero = $this->generateNumero();
            $sortie->save();

        }



        /*
        |--------------------------------------------------------------------------
        | REMETTRE EN ATTENTE
        |--------------------------------------------------------------------------
        */

        if($sortie->statut === 'Validé') {


            $journal = Journaux::where(
                'reference',
                $sortie->numero
            )->first();



            if($journal && $journal->statut === 'Validé') {


                DB::rollBack();


                return back()->with(
                    'error',
                    'Impossible de remettre ce bon en attente car le journal est déjà validé.'
                );

            }



            if($journal){

                $journal->delete();

            }



            $sortie->update([

                'statut'=>'En attente',

                'date_validation'=>null,

                'valide_par'=>null,

            ]);



            DB::commit();



            return redirect()

                ->route('sortie-caisses.index')

                ->with(
                    'success',
                    'Le bon a été remis en attente.'
                );

        }





        /*
        |--------------------------------------------------------------------------
        | VALIDATION DU BON
        |--------------------------------------------------------------------------
        */


        $sortie->update([

            'statut'=>'Validé',

            'date_validation'=>now(),

            'valide_par'=>auth()->id(),

        ]);





        Journaux::updateOrCreate(

            [

                'reference'=>$sortie->numero

            ],


            [

                'user_id'=>auth()->id(),


                // AJOUT IMPORTANT
                'journal_type_id'=>$request->journal_type_id,


                'sortie_caisse_id'=>$sortie->id,


                'date'=>$sortie->date,


                'description'=>$sortie->motif,


                'piece_justificatif'=>$sortie->numero,

                'type'=>'depense',
                'mode_paiement'=>'espèce',


                'monnaie'=>$sortie->monnaie,


                'entrees_cdf'=>0,

                'entrees_usd'=>0,


                'sorties_cdf'=>$sortie->monnaie == 'CDF'
                    ? $sortie->montant
                    : 0,


                'sorties_usd'=>$sortie->monnaie == 'USD'
                    ? $sortie->montant
                    : 0,


                'statut'=>'En attente',

            ]

        );



        DB::commit();



        return redirect()

            ->route('sortie-caisses.index')

            ->with(
                'success',
                'Bon de sortie validé avec succès.'
            );



    } catch(\Exception $e) {


        DB::rollBack();


        dd([

            'message'=>$e->getMessage(),

            'line'=>$e->getLine(),

            'file'=>$e->getFile()

        ]);

    }
}
public function rejeter($id)
{
    $sortie = SortieCaisse::findOrFail($id);

    $sortie->update([
        'statut' => 'Rejeté'
    ]);

    return redirect()
        ->route('sortie-caisses.show', $id)
        ->with('success', 'Bon de sortie rejeté.');
}
public function attente($id)
{

    DB::beginTransaction();

    try {

        $sortie = SortieCaisse::findOrFail($id);


        // Vérifier si le journal existe
        $journal = Journaux::where(
            'reference',
            $sortie->numero
        )->first();



        // Si le journal est déjà validé, blocage
        if($journal && $journal->statut === 'Validé'){


            DB::rollBack();


            return back()->with(
                'error',
                'Vous ne pouvez pas remettre ce bon en attente car le journal comptable est déjà validé.'
            );

        }



        // Supprimer le journal provisoire
        if($journal){

            $journal->delete();

        }



        // Remettre le bon en attente

        $sortie->update([

            'statut'=>'En attente',

            'date_validation'=>null,

            'valide_par'=>null,

        ]);



        DB::commit();


        return back()->with(
            'success',
            'Le bon de sortie a été remis en attente.'
        );



    } catch(\Exception $e){


        DB::rollBack();


        return back()->with(
            'error',
            $e->getMessage()
        );

    }

}

public function reouvrir($id, WorkflowComptableService $workflow)
{
    $sortie = SortieCaisse::findOrFail($id);
    Gate::authorize('reouvrir', $sortie);
    $workflow->reouvrirSortieCaisse($sortie);

    return back()->with('success', 'Bon de sortie réouvert avec succès.');
}
}
