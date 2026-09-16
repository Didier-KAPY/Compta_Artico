@foreach(['transport'=>'Transport','logement'=>'Logement','autres_avantages'=>'Autres avantages','telecommunication'=>'Télécommunication'] as $champ=>$libelle)
<div class="col-md-3"><label class="form-label">{{ $libelle }}</label><input type="number" name="{{ $champ }}" min="0" step="0.01" value="{{ old($champ, isset($paie) ? $paie->$champ : '') }}" class="form-control" placeholder="{{ in_array($champ, ['transport','logement']) ? 'Montant du contrat par défaut' : '0' }}"></div>
@endforeach
