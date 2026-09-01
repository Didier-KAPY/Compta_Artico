<?php
namespace App\Services;
use App\Models\Employe;
use Illuminate\Support\Facades\DB;
class MatriculeEmployeService { public function generer(int $entrepriseId):string { return DB::transaction(function()use($entrepriseId){ $max=Employe::withTrashed()->where('entreprise_id',$entrepriseId)->lockForUpdate()->count()+1; do{$m='EMP-'.now()->format('Y').'-'.str_pad((string)$max++,5,'0',STR_PAD_LEFT);}while(Employe::withTrashed()->where('entreprise_id',$entrepriseId)->where('matricule',$m)->exists()); return $m; }); } }
