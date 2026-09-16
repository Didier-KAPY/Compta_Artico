@if($paie->lignes->where('type','Gain')->isNotEmpty())
@foreach($paie->lignes->where('type','Gain') as $ligne)
<tr><td>{{ $ligne->libelle }}</td><td class="amount">{{ number_format($ligne->montant,2,',',' ') }}</td><td></td></tr>
@endforeach
@elseif((float)$paie->primes > 0)<tr><td>Primes et indemnités</td><td class="amount">{{ number_format($paie->primes,2,',',' ') }}</td><td></td></tr>@endif
@foreach(['transport'=>'Transport','logement'=>'Logement','autres_avantages'=>'Autres avantages','telecommunication'=>'Télécommunication','montant_heures_supplementaires'=>'Heures supplémentaires'] as $champ=>$libelle)
<tr><td>{{ $libelle }}</td><td class="amount">{{ (float)$paie->$champ ? number_format($paie->$champ,2,',',' ') : '—' }}</td><td></td></tr>
@endforeach
@foreach($paie->lignes->where('type','!=','Gain') as $ligne)
<tr><td>{{ $ligne->libelle }}</td><td></td><td class="amount">{{ $paie->appliquer_retenues ? number_format($ligne->montant,2,',',' ') : '—' }}</td></tr>
@endforeach
@if($paie->lignes->where('type','!=','Gain')->isEmpty())
@foreach(['total_taxes'=>'Impôts','total_cotisations'=>'Cotisations'] as $champ=>$libelle)
<tr><td>{{ $libelle }}</td><td></td><td class="amount">{{ $paie->appliquer_retenues && (float)$paie->$champ ? number_format($paie->$champ,2,',',' ') : '—' }}</td></tr>
@endforeach
@endif
@if(!$paie->appliquer_retenues)
@foreach(\App\Models\RhRubriquePaie::where('entreprise_id',$paie->entreprise_id)->where('actif',true)->where('type','!=','Gain')->get() as $rubrique)
@if(!$paie->lignes->contains('rubrique_id',$rubrique->id))
<tr><td>{{ $rubrique->libelle }}</td><td></td><td class="amount">—</td></tr>
@endif
@endforeach
@endif
<tr><td>Retenue sur absence</td><td></td><td class="amount">{{ $paie->appliquer_retenue_absence && (float)$paie->retenue_absence_theorique ? number_format($paie->retenue_absence_theorique,2,',',' ') : '—' }}</td></tr>
<tr><td>Autres retenues</td><td></td><td class="amount">{{ $paie->appliquer_retenues && (float)$paie->retenues ? number_format(max(0,(float)$paie->retenues-($paie->appliquer_retenue_absence?(float)$paie->retenue_absence_theorique:0)),2,',',' ') : '—' }}</td></tr>
