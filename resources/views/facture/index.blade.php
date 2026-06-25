@extends('layouts.app')

@section('content')

<div class="container">

    <h3>Liste des factures</h3>

    <a href="{{ route('factures.create') }}" class="btn btn-primary mb-3">
        Nouvelle facture
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N°</th>
                <th>Client</th>
                <th>Total</th>
                <th>Payé</th>
                <th>Reste</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            @foreach($factures as $facture)
            <tr>
                <td>{{ $facture->numero_facture }}</td>
                <td>{{ $facture->nom_client }}</td>
                <td>{{ $facture->montant_total }}</td>
                <td>{{ $facture->montant_paye }}</td>
                <td>{{ $facture->reste_a_payer }}</td>
                <td>{{ $facture->statut }}</td>
                <td>
                    <a href="{{ route('factures.show', $facture->id) }}" class="btn btn-info btn-sm">Voir</a>

                    <a href="{{ route('paiements.create', $facture->id) }}" class="btn btn-success btn-sm">
                        Payer
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection