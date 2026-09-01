<?php
namespace App\Services;
use App\Models\Entreprise;
use Illuminate\Contracts\Auth\Authenticatable;
class CurrentEntreprise { public function for(?Authenticatable $user=null): Entreprise { $user??=auth()->user(); return ($user?->entreprises()->orderBy('id')->first()) ?? Entreprise::orderBy('id')->firstOrFail(); } }
