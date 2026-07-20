@extends('layouts.app')

@section('title', 'Nouveau véhicule')

@section('content')
<h1 class="page-title mb-1"><i class="bi bi-plus-circle me-2"></i>Enregistrer un véhicule</h1>
<p class="page-subtitle">Certification blockchain à l'enregistrement</p>

@if($existingPlates->isNotEmpty())
<div class="alert alert-glass mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Immatriculations déjà utilisées :</strong>
    {{ $existingPlates->implode(', ') }} — choisissez une plaque différente (ex. <code>MN-456-OP</code>).
</div>
@endif

<div class="glass-card">
    <div class="glass-body">
        <form method="POST" action="{{ route('vehicles.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Marque</label>
                    <input type="text" name="brand" class="form-control glass-input @error('brand') is-invalid @enderror" value="{{ old('brand') }}" required>
                    @error('brand')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Modèle</label>
                    <input type="text" name="model" class="form-control glass-input @error('model') is-invalid @enderror" value="{{ old('model') }}" required>
                    @error('model')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Immatriculation</label>
                    <input type="text" name="plate_number" class="form-control glass-input @error('plate_number') is-invalid @enderror" value="{{ old('plate_number') }}" placeholder="Ex. MN-456-OP" required>
                    @error('plate_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kilométrage actuel</label>
                    <input type="number" name="current_mileage" class="form-control glass-input" value="{{ old('current_mileage', 0) }}" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contrôle technique</label>
                    <input type="date" name="technical_inspection_due" class="form-control glass-input" value="{{ old('technical_inspection_due') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiration assurance</label>
                    <input type="date" name="insurance_expiry" class="form-control glass-input" value="{{ old('insurance_expiry') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prochaine vidange</label>
                    <input type="date" name="next_oil_change" class="form-control glass-input" value="{{ old('next_oil_change') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Chauffeur assigné</label>
                    <select name="assigned_driver_id" class="form-select glass-select">
                        <option value="">— Aucun —</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" @selected(old('assigned_driver_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn glass-btn mt-4"><i class="bi bi-link-45deg me-1"></i>Enregistrer on-chain</button>
        </form>
    </div>
</div>
@endsection
