@extends('layouts.app')

@section('title', 'Véhicules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-truck me-2"></i>Gestion des véhicules</h1>
        <p class="page-subtitle mb-0">Gestion et suivi certifié de la flotte Emmaus</p>
    </div>
    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
        <a href="{{ route('vehicles.create') }}" class="btn glass-btn"><i class="bi bi-plus-lg me-1"></i>Nouveau véhicule</a>
    @endif
</div>

@if(auth()->user()->hasRole(\App\Enums\UserRole::Driver))
<div class="alert alert-glass-warning mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <i class="bi bi-info-circle me-2"></i>
        Vous êtes connecté comme <strong>Chauffeur</strong>. La création des véhicules et des comptes chauffeurs est réservée au gestionnaire.
    </div>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm glass-btn">
            <i class="bi bi-arrow-repeat me-1"></i>Changer de compte
        </button>
    </form>
</div>
@endif

<form class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select glass-select" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach(\App\Enums\VehicleStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table glass-table mb-0">
            <thead><tr><th>Immatriculation</th><th>Véhicule</th><th>Kilométrage</th><th>Statut</th><th>Chauffeur</th><th></th></tr></thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td><strong>{{ $vehicle->plate_number }}</strong></td>
                        <td>{{ $vehicle->full_name }}</td>
                        <td>{{ number_format($vehicle->current_mileage) }} km</td>
                        <td><span class="badge badge-glass-{{ $vehicle->status->color() }}">{{ $vehicle->status->label() }}</span></td>
                        <td>{{ $vehicle->assignedDriver?->name ?? '—' }}</td>
                        <td><a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm glass-btn-outline"><i class="bi bi-eye me-1"></i>Détails</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-glass py-4">Aucun véhicule enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $vehicles->links() }}</div>
@endsection
