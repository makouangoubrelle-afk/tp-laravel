@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="mb-4">
    <h1 class="page-title"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>
    <p class="page-subtitle">Vue d'ensemble — flotte, documents, alertes et intégrité blockchain</p>
</div>

@if($blockchainConfig['enabled'])
<div class="alert alert-glass-success mb-4">
    <i class="bi bi-diagram-3 me-2"></i>
    Blockchain réelle active sur <strong>{{ $blockchainConfig['network'] }}</strong>
    @if($blockchainConfig['contract'])
        — contrat
        <a class="text-white" target="_blank" rel="noopener"
           href="{{ rtrim($blockchainConfig['explorer'], '/') }}/address/{{ $blockchainConfig['contract'] }}">
            {{ substr($blockchainConfig['contract'], 0, 10) }}…{{ substr($blockchainConfig['contract'], -6) }}
        </a>
    @endif
</div>
@else
<div class="alert alert-glass-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Mode blockchain simulé : déployez le contrat Polygon puis configurez
    <code>BLOCKCHAIN_MODE=polygon</code>.
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="glass-card glass-stat"><h6><i class="bi bi-truck me-1"></i>Total véhicules</h6><h2>{{ $stats['total'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-success"><h6><i class="bi bi-check-circle me-1"></i>Disponibles</h6><h2>{{ $stats['available'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-warning"><h6><i class="bi bi-signpost-split me-1"></i>En mission</h6><h2>{{ $stats['on_mission'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-danger"><h6><i class="bi bi-tools me-1"></i>En réparation</h6><h2>{{ $stats['in_repair'] }}</h2></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="{{ route('alerts.index') }}" class="text-decoration-none">
            <div class="glass-card glass-stat stat-warning quick-link">
                <h6><i class="bi bi-bell me-1"></i>Alertes actives</h6>
                <h2>{{ $alertStats['pending'] }}</h2>
                @if($alertStats['critical'] > 0)<small class="text-danger">{{ $alertStats['critical'] }} critiques</small>@endif
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('documents.index') }}" class="text-decoration-none">
            <div class="glass-card glass-stat quick-link">
                <h6><i class="bi bi-folder2-open me-1"></i>Documents</h6>
                <h2>{{ $documentStats['total'] }}</h2>
                @if($documentStats['expiring'] > 0)<small class="text-warning">{{ $documentStats['expiring'] }} expirent bientôt</small>@endif
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('assignments.index') }}" class="text-decoration-none">
            <div class="glass-card glass-stat stat-success quick-link">
                <h6><i class="bi bi-person-lines-fill me-1"></i>Attributions</h6>
                <h2>{{ $activeAssignments->count() }}</h2>
                <small class="text-muted-glass">missions actives</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('history.index') }}" class="text-decoration-none">
            <div class="glass-card glass-stat quick-link">
                <h6><i class="bi bi-shield-lock me-1"></i>Intégrité</h6>
                <h2>{{ $integrityScore }}%</h2>
                <small class="text-muted-glass">score blockchain</small>
            </div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="glass-card mb-4">
            <div class="glass-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bell me-2"></i>Alertes prioritaires</span>
                <a href="{{ route('alerts.index') }}" class="btn btn-sm glass-btn-outline">Voir tout</a>
            </div>
            <div class="glass-body">
                @forelse($alerts as $alert)
                    <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 py-3">
                        <div>
                            <span class="badge badge-glass-{{ $alert->priority === 'critical' ? 'danger' : ($alert->priority === 'high' ? 'warning' : 'info') }} me-2">{{ $alert->priority }}</span>
                            <strong class="text-white">{{ $alert->vehicle->plate_number }}</strong>
                            <span class="text-muted-glass ms-2">{{ Str::limit($alert->message, 50) }}</span>
                        </div>
                        <span class="badge badge-glass-{{ $alert->due_date->isPast() ? 'danger' : 'info' }}">{{ $alert->due_date->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <p class="text-muted-glass mb-0"><i class="bi bi-check2-all me-1"></i>Aucune alerte en cours.</p>
                @endforelse
            </div>
        </div>

        @if($activeAssignments->isNotEmpty())
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-person-lines-fill me-2"></i>Attributions en cours</div>
            <div class="glass-body">
                @foreach($activeAssignments as $a)
                <div class="d-flex justify-content-between py-2 border-bottom border-light border-opacity-10">
                    <div>
                        <strong>{{ $a->vehicle->plate_number }}</strong> → {{ $a->driver->name }}
                    </div>
                    <span class="badge badge-glass-{{ $a->statusColor() }}">{{ $a->statusLabel() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="col-md-5">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-clock-history me-2"></i>Véhicules récents</div>
            <ul class="list-group list-group-flush">
                @foreach($recentVehicles as $v)
                    <li class="list-group-item glass-list-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('vehicles.show', $v) }}">{{ $v->full_name }} ({{ $v->plate_number }})</a>
                        <span class="badge badge-glass-{{ $v->status->color() }}">{{ $v->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
