<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h5>Informations de la paie</h5>
        <p class="text-muted">Informations enregistrées, en lecture seule. Les montants corrigibles figurent dans le formulaire ci-dessous.</p>
        <div class="row g-3">
            @foreach([
                'Employé' => trim(($paie->employe?->nom ?? '').' '.($paie->employe?->prenom ?? '')),
                'Matricule' => $paie->employe?->matricule,
                'Contrat' => $paie->contrat?->numero,
                'Période de paie' => $paie->periodePaie?->libelle,
                'Mois' => str_pad($paie->mois, 2, '0', STR_PAD_LEFT),
                'Année' => $paie->annee,
                'Statut' => $paie->statut,
                'Mode de rémunération' => $paie->contrat?->mode_remuneration,
                'Statut de la synthèse mensuelle' => $paie->syntheseMensuelle?->statut,
                'Jours présents' => $paie->syntheseMensuelle?->jours_presents,
                'Jours d’absence non rémunérée' => $paie->syntheseMensuelle?->jours_absence_non_remuneree,
                'Heures supplémentaires' => $paie->heures_supplementaires,
                'Retenue théorique pour absence' => $paie->retenue_absence_theorique,
                'Appliquer les retenues, taxes et cotisations' => $paie->appliquer_retenues ? 'Oui' : 'Non',
                'Total des gains' => $paie->total_gains,
                'Salaire brut' => $paie->salaire_brut,
                'Salaire imposable' => $paie->salaire_imposable,
                'Salaire net' => $paie->salaire_net,
                'Date de paiement' => $paie->date_paiement?->format('d/m/Y'),
                'Mode de paiement' => $paie->mode_paiement,
                'Référence de paiement' => $paie->reference_paiement,
                'Date de validation' => $paie->valide_le?->format('d/m/Y H:i'),
                'Bulletin généré le' => $paie->bulletin_genere_le?->format('d/m/Y H:i'),
                'Date de clôture' => $paie->cloture_le?->format('d/m/Y H:i'),
                'Créée le' => $paie->created_at?->format('d/m/Y H:i'),
                'Dernière correction le' => $paie->modifie_le?->format('d/m/Y H:i'),
                'Dernier motif de modification' => $paie->motif_modification,
                'Motif d’annulation' => $paie->motif_annulation,
            ] as $label => $valeur)
                <div class="col-md-4">
                    <label class="form-label" for="paie-detail-{{$loop->index}}">{{$label}}</label>
                    <input id="paie-detail-{{$loop->index}}" class="form-control bg-light" value="{{$valeur ?? '—'}}" readonly>
                </div>
            @endforeach
        </div>
        <h5 class="mt-4">Rubriques de paie</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Code</th><th>Libellé</th><th>Type</th><th>Base</th><th>Taux</th><th>Montant</th></tr></thead>
                <tbody>@forelse($paie->lignes as $ligne)
                    <tr><td>{{$ligne->code}}</td><td>{{$ligne->libelle}}</td><td>{{$ligne->type}}</td><td>{{$ligne->base}}</td><td>{{$ligne->taux ?? '—'}}</td><td>{{$ligne->montant}} {{$paie->monnaie}}</td></tr>
                @empty<tr><td colspan="6" class="text-muted">Aucune rubrique détaillée enregistrée.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <h5 class="mt-4">Paiements associés</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Date</th><th>Référence</th><th>Mode</th><th>Compte</th><th>Net à payer</th><th>Montant payé</th><th>Statut</th><th>Observation</th></tr></thead>
                <tbody>@forelse($paie->paiements as $paiement)
                    <tr><td>{{$paiement->date_paiement?->format('d/m/Y')}}</td><td>{{$paiement->reference_paiement ?? '—'}}</td><td>{{$paiement->mode_paiement}}</td><td>{{$paiement->compte_paiement ?? '—'}}</td><td>{{$paiement->montant_net_a_payer}} {{$paiement->devise}}</td><td>{{$paiement->montant_paye}} {{$paiement->devise}}</td><td>{{$paiement->statut}}</td><td>{{$paiement->observation ?? '—'}}</td></tr>
                @empty<tr><td colspan="8" class="text-muted">Aucun paiement enregistré.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</div>
