<?php
namespace App\Http\Controllers;
use App\Models\RhRubriquePaie;use App\Services\CurrentEntreprise;use Illuminate\Http\Request;use Illuminate\Support\Str;
class RhPayrollSettingController extends Controller
{
 public function index(CurrentEntreprise $c){return view('ressources_humaines.paie.parametres',['rubriques'=>RhRubriquePaie::where('entreprise_id',$c->for()->id)->orderBy('type')->orderBy('code')->paginate(30)]);}
 public function store(Request $r,CurrentEntreprise $c){$id=$c->for()->id;$d=$this->data($r);$base=Str::upper(Str::slug($d['libelle'],'_'))?:'RUBRIQUE';$code=Str::limit($base,24,'');$i=1;while(RhRubriquePaie::where('entreprise_id',$id)->where('code',$code)->exists())$code=Str::limit($base,20,'').'_'.$i++;RhRubriquePaie::create($d+['entreprise_id'=>$id,'code'=>$code]);return back()->with('success','Rubrique créée.');}
 public function update(Request $r,RhRubriquePaie $rubrique,CurrentEntreprise $c){abort_unless($rubrique->entreprise_id===$c->for()->id,404);$rubrique->update($this->data($r));return back()->with('success','Rubrique mise à jour.');}
 public function destroy(RhRubriquePaie $rubrique,CurrentEntreprise $c){abort_unless($rubrique->entreprise_id===$c->for()->id,404);$rubrique->delete();return back()->with('success','Rubrique supprimée. Les anciennes lignes de paie restent conservées.');}
 private function data(Request $r):array{return $r->validate(['libelle'=>'required|string|max:120','type'=>'required|in:Gain,Retenue,Taxe,Cotisation','mode_calcul'=>'required|in:Montant fixe,Taux','valeur'=>'required|numeric|min:0','base_calcul'=>'required|in:Salaire de base,Salaire brut','imposable'=>'nullable|boolean','soumis_cotisation'=>'nullable|boolean','actif'=>'nullable|boolean'])+['imposable'=>$r->boolean('imposable'),'soumis_cotisation'=>$r->boolean('soumis_cotisation'),'actif'=>$r->boolean('actif')];}
}
