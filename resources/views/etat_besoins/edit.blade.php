@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h4>Modifier État de Besoin</h4>

    <form action="{{ route('etat-besoins.update', $etat->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Département</label>
            <select name="departement_id" class="form-select" required>
                @foreach($departements as $departement)
                    <option value="{{ $departement->id }}" {{ $etat->departement_id === $departement->id ? 'selected' : '' }}>{{ $departement->designation }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Demandeur</label>
            <input type="text" name="demandeur" class="form-control" value="{{ $etat->demandeur }}">
        </div>

        <button class="btn btn-primary">Modifier</button>

    </form>

</div>

@endsection
