@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h4>Modifier État de Besoin</h4>

    <form action="{{ route('etat-besoins.update', $etat->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Service</label>
            <input type="text" name="service" class="form-control" value="{{ $etat->service }}">
        </div>

        <div class="mb-3">
            <label>Demandeur</label>
            <input type="text" name="demandeur" class="form-control" value="{{ $etat->demandeur }}">
        </div>

        <button class="btn btn-primary">Modifier</button>

    </form>

</div>

@endsection