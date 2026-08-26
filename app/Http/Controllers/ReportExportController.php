<?php

namespace App\Http\Controllers;

use App\Models\EcritureComptable;
use App\Models\EntreeCaisse;
use App\Models\EtatBesoin;
use App\Models\Journaux;
use App\Models\SortieCaisse;
use App\Models\Entreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportExportController extends Controller
{
    public function ecritures(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable']);
        $query = EcritureComptable::with(['compte', 'user'])
            ->whereBetween('date', $this->periode($request));

        if ($request->filled('liste_des_comptes_id')) {
            $query->where('liste_des_comptes_id', $request->integer('liste_des_comptes_id'));
        }

        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Compte', 'Désignation', 'Débit CDF', 'Crédit CDF', 'Statut'];
        $rows = $records->map(fn ($item) => [
            $this->date($item->date),
            $item->compte?->compte ?? '-',
            $item->compte?->designation ?? '-',
            $this->montant($item->debit_cdf),
            $this->montant($item->credit_cdf),
            $item->statut,
        ]);

        $this->ajouterUtilisateurs($headers, $rows, $records, 1);
        return $this->telecharger($format, 'Écritures comptables', 'ecritures-comptables', $headers, $rows, $request);
    }

    public function journaux(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière']);
        $query = Journaux::with(['journalType', 'user'])->whereBetween('date', $this->periode($request));
        if ($request->filled('journal_type_id')) {
            $query->where('journal_type_id', $request->integer('journal_type_id'));
        }
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%'.$request->reference.'%');
        }

        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Référence', 'Journal', 'Description', 'Entrées CDF', 'Sorties CDF', 'Entrées USD', 'Sorties USD', 'Statut'];
        $rows = $records->map(fn ($item) => [
            $this->date($item->date), $item->reference ?? '-', $item->journalType?->code ?? '-',
            $item->description ?? '-', $this->montant($item->entrees_cdf), $this->montant($item->sorties_cdf),
            $this->montant($item->entrees_usd), $this->montant($item->sorties_usd), $item->statut,
        ]);
        $this->ajouterUtilisateurs($headers, $rows, $records, 2);
        return $this->telecharger($format, 'Journaux de trésorerie', 'journaux-tresorerie', $headers, $rows, $request, 'landscape');
    }

    public function entrees(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière']);
        $query = EntreeCaisse::with('user')->whereBetween('date', $this->periode($request));
        $this->numero($query, $request);
        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Numéro', 'Motif', 'Montant', 'Monnaie', 'Statut'];
        $rows = $records->map(fn ($item) => [
            $this->date($item->date), $item->numero, $item->motif,
            $this->montant($item->montant), $item->monnaie, $item->statut,
        ]);
        $this->ajouterUtilisateurs($headers, $rows, $records, 2);
        return $this->telecharger($format, 'Entrées de caisse', 'entrees-caisse', $headers, $rows, $request);
    }

    public function sorties(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable', 'Caissier', 'Caissière', 'Trésorier', 'Trésorière']);
        $query = SortieCaisse::with('user')->whereBetween('date', $this->periode($request));
        $this->numero($query, $request);
        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Numéro', 'Bénéficiaire', 'Motif', 'Montant', 'Monnaie', 'Statut'];
        $rows = $records->map(fn ($item) => [
            $this->date($item->date), $item->numero, $item->beneficiaire, $item->motif,
            $this->montant($item->montant), $item->monnaie, $item->statut,
        ]);
        $this->ajouterUtilisateurs($headers, $rows, $records, 2);
        return $this->telecharger($format, 'Sorties de caisse', 'sorties-caisse', $headers, $rows, $request, 'landscape');
    }

    public function etatBesoins(Request $request, string $format)
    {
        $query = EtatBesoin::with(['user', 'departement', 'lignes'])->whereBetween('date', $this->periode($request));
        $user = $request->user();
        if (!$user->hasRole(['Super Admin', 'Admin', 'Gérant', 'Gerant'])) {
            $query->where(fn ($q) => $user->departement_id
                ? $q->where('departement_id', $user->departement_id)->orWhere('user_id', $user->id)
                : $q->where('user_id', $user->id));
        } elseif ($request->filled('departement_id')) {
            $query->where('departement_id', $request->integer('departement_id'));
        }
        $this->numero($query, $request);
        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Numéro', 'Département', 'Demandeur', 'Désignation', 'Montant', 'Monnaie', 'Statut'];
        $rows = $records->map(fn ($item) => [
            $this->date($item->date), $item->numero, $item->departement?->designation ?? $item->service,
            $item->demandeur, $item->lignes->pluck('designation')->join(', '), $this->montant($item->montant_estime),
            $item->monnaie, $item->statut,
        ]);
        $this->ajouterUtilisateurs($headers, $rows, $records, 2);
        return $this->telecharger($format, 'États de besoin', 'etats-besoin', $headers, $rows, $request, 'landscape');
    }

    public function grandLivre(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin', 'Admin', 'Directeur Général', 'DAF', 'Comptable']);
        $request->validate(['liste_des_comptes_id' => ['required', 'exists:liste_des_comptes,id']]);
        $query = EcritureComptable::with(['compte', 'journal', 'user'])
            ->where('statut', 'Validé')->where('liste_des_comptes_id', $request->integer('liste_des_comptes_id'))
            ->whereBetween('date', $this->periode($request));
        $records = $query->orderBy('date')->orderBy('id')->get();
        $headers = ['Date', 'Compte', 'Désignation', 'Débit CDF', 'Crédit CDF', 'Solde CDF'];
        $solde = 0;
        $rows = $records->map(function ($item) use (&$solde) {
            $solde += (float) $item->debit_cdf - (float) $item->credit_cdf;
            return [$this->date($item->date), $item->compte?->compte ?? '-', $item->compte?->designation ?? '-',
                $this->montant($item->debit_cdf), $this->montant($item->credit_cdf), $this->montant($solde)];
        });
        $this->ajouterUtilisateurs($headers, $rows, $records, 1);
        return $this->telecharger($format, 'Grand livre', 'grand-livre', $headers, $rows, $request, 'landscape');
    }

    public function tresorerie(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin','Admin','Directeur Général','DAF','Comptable','Caissier','Caissière','Trésorier','Trésorière']);
        [$debut,$fin] = $this->periode($request);
        $records = Journaux::query()->select('journal_type_id')->selectRaw('SUM(entrees_cdf) entree_cdf, SUM(sorties_cdf) sortie_cdf, SUM(entrees_usd) entree_usd, SUM(sorties_usd) sortie_usd')->with('journalType.compte')->where('statut','Validé')->whereHas('journalType',fn($q)=>$q->where('est_tresorerie',true))->whereBetween('date',[$debut,$fin])->groupBy('journal_type_id')->get();
        $headers=['Journal','Compte','Désignation','Nature','Entrées CDF','Sorties CDF','Solde CDF','Entrées USD','Sorties USD','Solde USD'];
        $rows=$records->map(fn($i)=>[$i->journalType?->code??'-',$i->journalType?->compte?->compte??'-',$i->journalType?->compte?->designation??'-',ucfirst(str_replace('_',' ',$i->journalType?->nature??'-')),$this->montant($i->entree_cdf),$this->montant($i->sortie_cdf),$this->montant($i->entree_cdf-$i->sortie_cdf),$this->montant($i->entree_usd),$this->montant($i->sortie_usd),$this->montant($i->entree_usd-$i->sortie_usd)]);
        return $this->telecharger($format,'Situation de trésorerie','situation-tresorerie',$headers,$rows,$request,'landscape');
    }

    public function releve(Request $request, string $format)
    {
        $this->autoriser($request, ['Super Admin','Admin','Directeur Général','DAF','Comptable','Caissier','Caissière','Trésorier','Trésorière']); [$debut,$fin]=$this->periode($request);
        $base=Journaux::query()->where('statut','Validé')->whereHas('journalType',fn($q)=>$q->where('est_tresorerie',true))->when($request->filled('journal_type_id'),fn($q)=>$q->where('journal_type_id',$request->integer('journal_type_id')));
        $ouv=(clone $base)->whereDate('date','<',$debut)->selectRaw('COALESCE(SUM(entrees_cdf),0)-COALESCE(SUM(sorties_cdf),0) cdf, COALESCE(SUM(entrees_usd),0)-COALESCE(SUM(sorties_usd),0) usd')->first();
        $records=(clone $base)->with(['journalType.compte','compte'])->whereBetween('date',[$debut,$fin])->orderBy('date')->orderBy('id')->get(); $cdf=(float)$ouv->cdf; $usd=(float)$ouv->usd;
        $headers=['Date','Référence','Journal','Compte','Libellé','Entrée CDF','Sortie CDF','Solde CDF','Entrée USD','Sortie USD','Solde USD']; $rows=collect([[$this->date($debut),'SOLDE D’OUVERTURE','','','','','',$this->montant($cdf),'','',$this->montant($usd)]]);
        foreach($records as $i){$cdf+=(float)$i->entrees_cdf-(float)$i->sorties_cdf;$usd+=(float)$i->entrees_usd-(float)$i->sorties_usd;$rows->push([$this->date($i->date),$i->reference,$i->journalType?->code??'-',$i->compte?->compte??$i->journalType?->compte?->compte??'-',$i->libelle_releve,$this->montant($i->entrees_cdf),$this->montant($i->sorties_cdf),$this->montant($cdf),$this->montant($i->entrees_usd),$this->montant($i->sorties_usd),$this->montant($usd)]);}
        return $this->telecharger($format,'Relevé de trésorerie','releve-tresorerie',$headers,$rows,$request,'landscape');
    }

    public function balance(Request $request, string $format)
    {
        $this->autoriser($request,['Super Admin','Admin','Directeur Général','DAF','Comptable']); $this->periode($request); $data=app(BalanceController::class)->index($request)->getData();
        $headers=['Compte','Désignation','Initial débiteur','Initial créditeur','Mouvement débit','Mouvement crédit','Final débiteur','Final créditeur'];
        $rows=$data['balance']->map(fn($l)=>[$l['compte'],$l['designation'],$this->montant($l['initial_debit']),$this->montant($l['initial_credit']),$this->montant($l['mouvement_debit']),$this->montant($l['mouvement_credit']),$this->montant($l['final_debit']),$this->montant($l['final_credit'])]);
        return $this->telecharger($format,'Balance générale','balance-generale',$headers,$rows,$request,'landscape');
    }

    public function bilan(Request $request,string $format)
    {
        $this->autoriser($request,['Super Admin','Admin','Directeur Général','DAF','Comptable']); $data=app(EtatFinancierController::class)->bilan($request)->getData(); $rows=collect();
        foreach(['actif'=>'ACTIF','passif'=>'PASSIF'] as $sens=>$type) foreach($data['etats']['bilan'][$sens] as $s){$rows->push([$type,'',$s['label'],$this->montant($s['total_actuel']),$this->montant($s['total_precedent'])]);foreach($s['lignes'] as $l)$rows->push([$type,$l['code'],$l['label'],$this->montant($l['actuel']),$this->montant($l['precedent'])]);}
        return $this->telecharger($format,'Bilan final','bilan-final',['Type','Réf.','Libellé','Exercice N','Exercice N-1'],$rows,$request,'landscape');
    }

    public function compteResultat(Request $request,string $format)
    {
        $this->autoriser($request,['Super Admin','Admin','Directeur Général','DAF','Comptable']); $data=app(EtatFinancierController::class)->compteResultat($request)->getData(); $rows=collect();
        foreach(['produits_exploitation','charges_exploitation','produits_financiers','charges_financieres','produits_hao','charges_hao','impot_resultat'] as $key){$s=$data['etats']['compte_resultat'][$key];$rows->push(['',$s['label'],$this->montant($s['total_actuel']),$this->montant($s['total_precedent'])]);foreach($s['lignes'] as $l)$rows->push([$l['code'],$l['label'],$this->montant($l['actuel']),$this->montant($l['precedent'])]);}
        $net=$data['etats']['compte_resultat']['resultat_net'];$rows->push(['','RÉSULTAT NET',$this->montant($net['actuel']),$this->montant($net['precedent'])]); return $this->telecharger($format,'Compte de résultat','compte-resultat',['Réf.','Libellé','Exercice N','Exercice N-1'],$rows,$request,'landscape');
    }

    private function periode(Request $request): array
    {
        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);
        return [$validated['date_debut'], $validated['date_fin']];
    }

    private function numero(Builder $query, Request $request): void
    {
        if ($request->filled('numero')) $query->where('numero', 'like', '%'.$request->numero.'%');
    }

    private function ajouterUtilisateurs(array &$headers, Collection &$rows, Collection $records, int $position): void
    {
        if (!auth()->user()?->hasRole('Super Admin')) return;
        array_splice($headers, $position, 0, ['Utilisateur']);
        $rows = $rows->values()->map(function ($row, $index) use ($records, $position) {
            $record = $records->values()->get($index);
            $nom = trim(($record->user?->prenom ?? '').' '.($record->user?->nom ?? '')) ?: 'Système';
            array_splice($row, $position, 0, [$nom]);
            return $row;
        });
    }

    private function montant($value): string { return number_format((float) $value, 2, ',', ' '); }

    private function date($value): string { return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-'; }

    private function autoriser(Request $request, array $roles): void
    {
        abort_unless($request->user()?->hasRole($roles), 403);
    }

    private function telecharger(string $format, string $titre, string $nom, array $headers, Collection $rows, Request $request, string $orientation = 'portrait')
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $data = ['titre' => $titre, 'headers' => $headers, 'rows' => $rows, 'entreprise' => Entreprise::first(),
            'dateDebut' => $request->date_debut, 'dateFin' => $request->date_fin];
        $suffixe = $request->date_debut.'_'.$request->date_fin;
        if ($format === 'pdf') {
            return Pdf::loadView('exports.table', $data)->setPaper('a4', $orientation)->download($nom.'_'.$suffixe.'.pdf');
        }
        $contenu = view('exports.table_excel', $data)->render();
        return response("\xEF\xBB\xBF".$contenu, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nom.'_'.$suffixe.'.xls"',
        ]);
    }
}
