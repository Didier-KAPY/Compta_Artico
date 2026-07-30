@extends('layouts.app')
@section('title','Bon de Régularisation Comptable')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body bg-white" id="brc-document">
            {{-- =====================================================
                ENTETE ENTREPRISE
            ====================================================== --}}
            <table class="table table-borderless">
                <tr>
                    <td width="20%">
                        @if($entreprise && $entreprise->logo)
                            <img src="{{ asset('storage/'.$entreprise->logo) }}"
                                 width="90">
                        @endif
                    </td>
                    <td class="text-center">
                        <h3 class="fw-bold">
                            {{ $entreprise?->nom_entreprise ?? config('app.name') }}
                        </h3>
                        <p class="mb-0">
                            {{ $entreprise?->adresse }}
                        </p>
                        <p class="mb-2">
                            {{ $entreprise?->telephone }}
                        </p>
                        <h4 class="fw-bold">
                            BON DE REGULARISATION COMPTABLE
                        </h4>
                        <h5 class="fw-bold">
                            {{ $numeroBrc }}
                        </h5>
                    </td>
                    <td width="20%" class="text-end">
                        <strong>
                            Date :
                        </strong>
                        <br>
                        {{ now()->format('d/m/Y') }}
                    </td>
                </tr>
            </table>
            <hr>
            {{-- =====================================================
                INFORMATIONS GENERALES
            ====================================================== --}}
            <table class="table table-borderless">
                <tr>
                    <td>
                        <strong>
                            Période :
                        </strong>
                        Du
                        {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                        au
                        {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                    </td>
                    <td class="text-end">
                        <strong>
                            Préparé par :
                        </strong>
                        {{ trim((auth()->user()->prenom ?? '').' '.(auth()->user()->nom ?? '')) ?: 'Utilisateur' }}
                    </td>
                </tr>
            </table>
            {{-- =====================================================
                DETAIL DES ECRITURES
            ====================================================== --}}
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>
                            Date
                        </th>
                        <th>
                            Journal
                        </th>
                        <th>
                            Pièce
                        </th>
                        <th>
                            Référence
                        </th>
                        <th>
                            Compte
                        </th>
                        <th>
                            Libellé
                        </th>
                        <th class="text-end">
                            Débit CDF
                        </th>
                        <th class="text-end">
                            Crédit CDF
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brc as $ligne)
                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse(
                                    $ligne['date']
                                )->format('d/m/Y') }}
                            </td>
                            <td>
                                {{ $ligne['journal'] }}
                            </td>
                            <td>
                                {{ $ligne['piece'] }}
                            </td>
                            <td>
                                {{ $ligne['reference'] }}
                            </td>
                            <td class="fw-bold">
                                {{ $ligne['compte'] }}
                            </td>
                            <td>
                                {{ $ligne['libelle'] }}
                            </td>
                            <td class="text-end">
                                {{ number_format(
                                    $ligne['debit'],
                                    2,
                                    ',',
                                    ' '
                                ) }}
                            </td>
                            <td class="text-end">
                                {{ number_format(
                                    $ligne['credit'],
                                    2,
                                    ',',
                                    ' '
                                ) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune écriture validée pour ces critères.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="6"
                            class="text-end">
                            TOTAL GENERAL
                        </td>
                        <td class="text-end">
                            {{ number_format(
                                $totalDebit,
                                2,
                                ',',
                                ' '
                            ) }}
                        </td>
                        <td class="text-end">
                            {{ number_format(
                                $totalCredit,
                                2,
                                ',',
                                ' '
                            ) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6"
                            class="text-end fw-bold">
                            Contrôle équilibre
                        </td>
                        <td colspan="2"
                            class="text-center">
                            @if(abs($ecart) < 0.01)
                                <span class="badge bg-success">
                                    ECRITURE EQUILIBREE
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    ECART :
                                    {{ number_format(
                                        abs($ecart),
                                        2,
                                        ',',
                                        ' '
                                    ) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
            {{-- =====================================================
                SIGNATURES
            ====================================================== --}}
            <br><br>
            <table class="table table-borderless">
                <tr>
                    <td class="text-center">
                        <strong>
                            Préparé par
                        </strong>
                        <br><br><br>
                        __________________________
                        <br>
                        {{ auth()->user()->nom ?? 'Utilisateur' }}
                    </td>
                    <td class="text-center">
                        <strong>
                            Validé par
                        </strong>
                        <br><br><br>
                        __________________________
                    </td>
                </tr>
            </table>
            <div class="text-center mt-3">
                Impression :
                {{ date('d/m/Y H:i') }}
            </div>
        </div>
        <div class="mt-3 no-print">
        <a href="{{ route(
            'ecritures.brc.pdf',
            [
                'date_debut'=>$dateDebut,
                'date_fin'=>$dateFin,
                'journal_type_id'=>$journalSelectionne?->id
            ]
        ) }}"
        class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i>
        Télécharger PDF
        </a>
        <button onclick="window.print()"
        class="btn btn-secondary">
        <i class="bi bi-printer"></i>
        Imprimer
        </button>
        </div>
    </div>
</div>

<style>
    #brc-document {
    padding:40px;
    }
    #brc-document table {
    width:100%;
    }
    #brc-document th,
    #brc-document td {
    padding:8px;
    }
    @media print {
    .card-header,
    button,
    .navbar,
    .sidebar {
        display:none !important;
    }
    body {
        font-size:12px;
    }
    #brc-document {
        padding:0;
    }
    }
</style>



@endsection
