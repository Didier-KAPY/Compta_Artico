<?php
namespace App\Http\Controllers;
use App\Models\{Employe,RhDocument};
use App\Services\{AuditLogService,CurrentEntreprise};
use Illuminate\Http\{Request,Response};
use Illuminate\Support\Facades\Storage;
class RhDocumentController extends Controller {
 public function store(Request $r,Employe $employe,CurrentEntreprise $ctx){abort_unless($employe->entreprise_id===$ctx->for()->id,404);$d=$r->validate(['type'=>'required|in:contrat,avenant,identite,diplome,certificat,medical,bulletin_paie,sanction,sortie,autre','fichier'=>'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx','date_expiration'=>'nullable|date']);$f=$r->file('fichier');$path=$f->store('rh/'.$employe->entreprise_id.'/'.$employe->id,'local');$doc=RhDocument::create(['entreprise_id'=>$employe->entreprise_id,'employe_id'=>$employe->id,'type'=>$d['type'],'nom_original'=>$f->getClientOriginalName(),'chemin'=>$path,'mime_type'=>$f->getMimeType(),'taille'=>$f->getSize(),'date_expiration'=>$d['date_expiration']??null,'televerse_par'=>$r->user()->id]);app(AuditLogService::class)->record('creation_document_rh',$doc,(string)$doc->id,null,null,['type'=>$doc->type,'nom'=>$doc->nom_original],[],$r);return back()->with('success','Document ajouté.');}
 public function download(Request $r,RhDocument $document,CurrentEntreprise $ctx){abort_unless($document->entreprise_id===$ctx->for()->id,404);app(AuditLogService::class)->record('telechargement_document_rh',$document,(string)$document->id,null,null,['type'=>$document->type],[],$r);abort_unless(Storage::disk('local')->exists($document->chemin),404);return Storage::disk('local')->download($document->chemin,$document->nom_original,['Content-Type'=>$document->mime_type]);}
}
