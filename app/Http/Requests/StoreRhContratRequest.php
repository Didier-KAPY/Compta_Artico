<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreRhContratRequest extends FormRequest {
 public function authorize():bool{return $this->user()?->can('manageContracts')??false;}
 protected function prepareForValidation():void{$this->merge(['mode_remuneration'=>$this->input('mode_remuneration','Mensuel'),'jours_reference'=>$this->input('jours_reference',26),'heures_reference'=>$this->input('heures_reference',173.33)]);}
 public function rules():array{return [
  'employe_id'=>'required|exists:rh_employes,id','numero'=>'nullable|string|max:50|unique:rh_contrats,numero','type'=>['required',Rule::in(['CDI','CDD','Essai','Stage','Consultant / Prestataire','Consultance','Journalier','Autre'])],
  'intitule_autre'=>'nullable|required_if:type,Autre|string|max:190','description'=>'nullable|required_if:type,Autre|string','conditions_particulieres'=>'nullable|required_if:type,Autre|string','date_signature'=>'nullable|date','date_debut'=>'required|date','date_fin'=>['nullable','date','after_or_equal:date_debut',Rule::requiredIf(fn()=>in_array($this->type,['CDD','Essai','Stage'],true))],
  'poste'=>'nullable|string|max:190','service_id'=>'nullable|exists:rh_services,id','superieur_id'=>'nullable|exists:rh_employes,id','description_fonctions'=>'nullable|string','periode_essai_jours'=>'nullable|integer|min:0|max:730','heures_hebdomadaires'=>'required|numeric|min:1|max:168','heures_quotidiennes'=>'nullable|numeric|min:0|max:24','pause_minutes'=>'nullable|integer|min:0|max:1440',
  'salaire_base'=>'required|numeric|min:0','devise'=>'required|in:CDF,USD','mode_remuneration'=>['required',Rule::in(['Mensuel','Journalier','Horaire','Forfait','Par mission','Par livrable','Autre'])],'jours_travail'=>'nullable|array','jours_travail.*'=>'integer|min:1|max:7','heure_debut_prevue'=>'nullable|date_format:H:i','heure_fin_prevue'=>'nullable|date_format:H:i|after:heure_debut_prevue',
  'primes_fixes'=>'nullable|numeric|min:0','indemnites_fixes'=>'nullable|numeric|min:0','prime_fonction'=>'nullable|numeric|min:0','prime_transport'=>'nullable|numeric|min:0','prime_logement'=>'nullable|numeric|min:0','prime_responsabilite'=>'nullable|numeric|min:0','jours_reference'=>'required|numeric|min:1','heures_reference'=>'required|numeric|min:1','lieu_travail'=>'nullable|string|max:190',
  'regle_presence'=>'nullable|boolean','regle_conge'=>'nullable|boolean','regle_retenue_absence'=>'nullable|boolean','regle_heures_supplementaires'=>'nullable|boolean','details_specifiques'=>'nullable|array','statut'=>'required|in:Brouillon,À vérifier,Validé,En attente de validation,Actif,Suspendu,Expiré,Rompu,Terminé,Annulé','observations'=>'nullable|string'];}
}
