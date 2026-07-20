@extends('layouts.app')

@section('title', $vehicle->plate_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title">{{ $vehicle->full_name }}</h1>
        <p class="page-subtitle mb-0">
            <i class="bi bi-tag me-1"></i>{{ $vehicle->plate_number }} · {{ number_format($vehicle->current_mileage) }} km
            @if($vehicle->blockchain_hash)<span class="badge badge-certified ms-2"><i class="bi bi-shield-check me-1"></i>Certifié blockchain</span>@endif
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('history.show', $vehicle) }}" class="btn glass-btn-outline btn-sm"><i class="bi bi-shield-lock me-1"></i>Historique certifié</a>
        @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn glass-btn-outline btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
        @endif
        <span class="badge badge-glass-{{ $vehicle->status->color() }} fs-6">{{ $vehicle->status->label() }}</span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card mb-4">
            <div class="glass-header">
                <i class="bi bi-clock-history me-2"></i>Timeline véhicule
                <small class="text-muted-glass ms-2">— Données certifiées + administratives</small>
            </div>
            <div class="glass-body">
                @forelse($timeline as $event)
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $event['title'] }}</strong>
                            <small class="text-muted-glass">{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y H:i') }}</small>
                        </div>
                        @if($event['description'])<p class="mb-1 text-muted-glass">{{ $event['description'] }}</p>@endif
                        @if($event['certified'])<code class="small">{{ $event['hash'] }}</code>@endif
                    </div>
                @empty
                    <p class="text-muted-glass mb-0">Aucun événement enregistré.</p>
                @endforelse
            </div>
        </div>

        @if(!auth()->user()->role->isReadOnly())
        <div class="row g-3">
            @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
            <div class="col-md-6"><div class="glass-card"><div class="glass-header"><i class="bi bi-person-check me-1"></i>Assigner au chauffeur</div><div class="glass-body">
                <form method="POST" action="{{ route('assignments.store', $vehicle) }}">@csrf
                    <select name="driver_id" class="form-select glass-select mb-2" required>@foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                    <input type="number" name="start_mileage" class="form-control glass-input mb-2" placeholder="Km départ" value="{{ $vehicle->current_mileage }}" required>
                    <button class="btn glass-btn btn-sm">Assigner</button>
                </form>
            </div></div></div>
            @endif

            @if(auth()->user()->hasRole(\App\Enums\UserRole::Driver, \App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin))
            <div class="col-md-6"><div class="glass-card"><div class="glass-header"><i class="bi bi-speedometer me-1"></i>Relevé compteur certifié</div><div class="glass-body">
                <form method="POST" action="{{ route('odometer.store', $vehicle) }}">@csrf
                    <input type="number" name="mileage" class="form-control glass-input mb-2" placeholder="Kilométrage" min="{{ $vehicle->current_mileage }}" required>
                    <button class="btn glass-btn btn-sm"><i class="bi bi-link-45deg me-1"></i>Certifier on-chain</button>
                </form>
            </div></div></div>

            <div class="col-md-6"><div class="glass-card"><div class="glass-header"><i class="bi bi-fuel-pump me-1"></i>Plein / Consommation</div><div class="glass-body">
                <form method="POST" action="{{ route('fuel.store', $vehicle) }}">@csrf
                    <input type="number" step="0.01" name="liters" class="form-control glass-input mb-2" placeholder="Litres" required>
                    <input type="number" step="0.01" name="cost" class="form-control glass-input mb-2" placeholder="Coût €" required>
                    <input type="number" name="mileage_at_fill" class="form-control glass-input mb-2" placeholder="Km au plein" required>
                    <input type="text" name="slip_reference" class="form-control glass-input mb-2" placeholder="Réf. ticket">
                    <button class="btn glass-btn btn-sm">Enregistrer</button>
                </form>
            </div></div></div>
            @endif

            @if(auth()->user()->hasRole(\App\Enums\UserRole::Mechanic, \App\Enums\UserRole::SuperAdmin))
            <div class="col-md-6"><div class="glass-card"><div class="glass-header"><i class="bi bi-tools me-1"></i>Maintenance certifiée</div><div class="glass-body">
                <form method="POST" action="{{ route('maintenance.store', $vehicle) }}">@csrf
                    <input type="date" name="service_date" class="form-control glass-input mb-2" required>
                    <input type="text" name="intervention_type" class="form-control glass-input mb-2" placeholder="Type intervention" required>
                    <textarea name="description" class="form-control glass-input mb-2" placeholder="Description" rows="2"></textarea>
                    <input type="text" name="parts_replaced" class="form-control glass-input mb-2" placeholder="Pièces (séparées par virgule)">
                    <input type="number" name="mileage_at_service" class="form-control glass-input mb-2" placeholder="Km" required>
                    <input type="number" step="0.01" name="cost" class="form-control glass-input mb-2" placeholder="Coût">
                    <button class="btn glass-btn btn-sm"><i class="bi bi-shield-check me-1"></i>Certifier maintenance</button>
                </form>
            </div></div></div>
            @endif

            @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
            <div class="col-md-6"><div class="glass-card"><div class="glass-header"><i class="bi bi-file-earmark me-1"></i>Document administratif</div><div class="glass-body">
                <form method="POST" action="{{ route('documents.store', $vehicle) }}" enctype="multipart/form-data">@csrf
                    <select name="type" class="form-select glass-select mb-2" required>
                        <option value="registration">Carte grise</option>
                        <option value="insurance">Assurance</option>
                        <option value="invoice">Facture</option>
                        <option value="contract">Contrat</option>
                        <option value="inspection">Contrôle technique</option>
                        <option value="other">Autre</option>
                    </select>
                    <input type="text" name="title" class="form-control glass-input mb-2" placeholder="Titre" required>
                    <input type="file" name="file" class="form-control glass-input mb-2" required>
                    <input type="date" name="expiry_date" class="form-control glass-input mb-2">
                    <button class="btn glass-btn btn-sm">Archiver</button>
                </form>
            </div></div></div>
            @endif
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="glass-card mb-3">
            <div class="glass-header"><i class="bi bi-info-circle me-1"></i>Informations</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item glass-list-item">CT : {{ $vehicle->technical_inspection_due?->format('d/m/Y') ?? '—' }}</li>
                <li class="list-group-item glass-list-item">Assurance : {{ $vehicle->insurance_expiry?->format('d/m/Y') ?? '—' }}</li>
                <li class="list-group-item glass-list-item">Vidange : {{ $vehicle->next_oil_change?->format('d/m/Y') ?? '—' }}</li>
                <li class="list-group-item glass-list-item">Chauffeur : {{ $vehicle->assignedDriver?->name ?? '—' }}</li>
            </ul>
        </div>

        <div class="glass-card mb-3">
            <div class="glass-header"><i class="bi bi-folder me-1"></i>Documents</div>
            <ul class="list-group list-group-flush">
                @forelse($vehicle->documents as $doc)
                    <li class="list-group-item glass-list-item d-flex justify-content-between align-items-center">
                        <span>{{ $doc->title }}</span>
                        <div class="d-flex gap-1">
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm glass-btn-outline"><i class="bi bi-download"></i></a>
                            @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
                            <form action="{{ route('documents.verify', $doc) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm glass-btn-outline"><i class="bi bi-check-lg"></i></button></form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item glass-list-item text-muted-glass">Aucun document.</li>
                @endforelse
            </ul>
        </div>

        @foreach($vehicle->assignments->whereNull('returned_at') as $assignment)
        <div class="glass-card mb-3" style="border: 1px solid rgba(255, 209, 102, 0.4);">
            <div class="glass-header"><i class="bi bi-signpost-split me-1"></i>Mission en cours</div>
            <div class="glass-body">
                <p class="mb-2">Chauffeur : <strong>{{ $assignment->driver->name }}</strong><br>Départ : {{ $assignment->start_mileage }} km</p>
                @if(auth()->user()->hasRole(\App\Enums\UserRole::Driver, \App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin))
                <form method="POST" action="{{ route('assignments.return', $assignment) }}">@csrf
                    <input type="number" name="end_mileage" class="form-control glass-input mb-2" placeholder="Km retour" min="{{ $assignment->start_mileage }}" required>
                    <button class="btn glass-btn btn-sm"><i class="bi bi-arrow-return-left me-1"></i>Restituer véhicule</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
