@extends('layouts.app')

@section('content')

<div class="container">

    <h3>Paiement Facture : {{ $facture->numero_facture }}</h3>

    <form action="{{ route('paiements.store') }}" method="POST">
        @csrf

        <input type="hidden" name="facture_id" value="{{ $facture->id }}">

        <div class="mb-2">
            <label>Montant</label>
            <input type="number" name="montant" class="form-control">
        </div>

        <div class="mb-2">
            <label>Mode paiement</label>
            <select name="mode_paiement" class="form-control">
                <option value="cash">Cash</option>
                <option value="banque">Banque</option>
                <option value="mobile_money">Mobile Money</option>
            </select>
        </div>

        <div class="mb-2">
            <label>Compte de trésorerie</label>
            <select name="liste_des_comptes_id" class="form-control">
                @foreach($comptes as $compte)
                    <option value="{{ $compte->id }}">
                        {{ $compte->designation }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary">
            Valider paiement
        </button>

    </form>

</div>

@endsection