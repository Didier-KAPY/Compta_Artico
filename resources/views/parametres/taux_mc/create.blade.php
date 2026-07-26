@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h4 class="mb-0">
                <i class="bi bi-phone me-2"></i>
                Taux MC
            </h4>

            <small class="text-muted">
                Définir le taux Mobile Money
            </small>

        </div>

        <a href="{{ route('parametres.parametre') }}"
           class="btn btn-secondary">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-header bg-dark text-white">
            Taux Mobile Money
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('parametres.taux-mc.store') }}"
                  method="POST">

                @csrf

                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Taux MC
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="taux_mc"
                        class="form-control"
                        value="{{ old('taux_mc', $taux->taux_mc ?? '') }}"
                        placeholder="Ex : 1.05"
                        required>

                </div>

                <div class="text-end">

                    <button class="btn btn-success">

                        <i class="bi bi-save me-1"></i>

                        Enregistrer

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection