@extends('layouts.app')

@section('title', 'Modifier véhicule')

@section('content')
<h1 class="page-title mb-4"><i class="bi bi-pencil me-2"></i>Modifier {{ $vehicle->plate_number }}</h1>

<div class="glass-card">
    <div class="glass-body">
        <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Marque</label><input type="text" name="brand" class="form-control glass-input" value="{{ $vehicle->brand }}" required></div>
                <div class="col-md-4"><label class="form-label">Modèle</label><input type="text" name="model" class="form-control glass-input" value="{{ $vehicle->model }}" required></div>
                <div class="col-md-4"><label class="form-label">Immatriculation</label><input type="text" name="plate_number" class="form-control glass-input" value="{{ $vehicle->plate_number }}" required></div>
                <div class="col-md-4"><label class="form-label">Statut</label>
                    <select name="status" class="form-select glass-select">@foreach(\App\Enums\VehicleStatus::cases() as $s)<option value="{{ $s->value }}" @selected($vehicle->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                </div>
                <div class="col-md-4"><label class="form-label">Kilométrage</label><input type="number" name="current_mileage" class="form-control glass-input" value="{{ $vehicle->current_mileage }}" required></div>
                <div class="col-md-4"><label class="form-label">Chauffeur</label>
                    <select name="assigned_driver_id" class="form-select glass-select"><option value="">—</option>@foreach($drivers as $d)<option value="{{ $d->id }}" @selected($vehicle->assigned_driver_id == $d->id)>{{ $d->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-4"><label class="form-label">Contrôle technique</label><input type="date" name="technical_inspection_due" class="form-control glass-input" value="{{ $vehicle->technical_inspection_due?->format('Y-m-d') }}"></div>
                <div class="col-md-4"><label class="form-label">Assurance</label><input type="date" name="insurance_expiry" class="form-control glass-input" value="{{ $vehicle->insurance_expiry?->format('Y-m-d') }}"></div>
                <div class="col-md-4"><label class="form-label">Vidange</label><input type="date" name="next_oil_change" class="form-control glass-input" value="{{ $vehicle->next_oil_change?->format('Y-m-d') }}"></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn glass-btn"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn glass-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
