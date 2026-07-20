@extends('layouts.app')

@section('title', 'Historique — '.$vehicle->plate_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title">{{ $vehicle->full_name }}</h1>
        <p class="page-subtitle mb-0">Historique certifié — {{ $vehicle->plate_number }}</p>
    </div>
    <div class="text-end">
        <div class="integrity-score-sm">{{ $report['score'] }}%</div>
        <small class="text-muted-glass">Score de certification</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-speedometer2 me-1"></i>Kilométrage certifié</div>
            <div class="glass-body">
                @forelse($report['odometer'] as $item)
                <div class="integrity-record {{ $item['valid'] ? 'valid' : 'invalid' }}">
                    <div class="d-flex justify-content-between">
                        <strong>{{ number_format($item['mileage']) }} km</strong>
                        <i class="bi bi-{{ $item['valid'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }}"></i>
                    </div>
                    <small class="text-muted-glass">{{ $item['date']->format('d/m/Y H:i') }}</small>
                    @if($item['tx_hash'])<code class="d-block mt-1 small">{{ substr($item['tx_hash'], 0, 20) }}...</code>@endif
                </div>
                @empty
                <p class="text-muted-glass mb-0">Aucun relevé.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-tools me-1"></i>Historique d'entretien</div>
            <div class="glass-body">
                @forelse($report['maintenances'] as $item)
                <div class="integrity-record {{ $item['valid'] ? 'valid' : 'invalid' }}">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $item['type'] }}</strong>
                        <i class="bi bi-{{ $item['valid'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }}"></i>
                    </div>
                    <small class="text-muted-glass">{{ $item['date']->format('d/m/Y') }}</small>
                    @if($item['tx_hash'])<code class="d-block mt-1 small">{{ substr($item['tx_hash'], 0, 20) }}...</code>@endif
                </div>
                @empty
                <p class="text-muted-glass mb-0">Aucune maintenance.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-person-lines-fill me-1"></i>Attributions</div>
            <div class="glass-body">
                @forelse($report['assignments'] as $item)
                <div class="integrity-record {{ $item['valid'] ? 'valid' : 'invalid' }}">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $item['driver'] ?? '—' }}</strong>
                        <i class="bi bi-{{ $item['valid'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }}"></i>
                    </div>
                    <small class="text-muted-glass">{{ $item['date']->format('d/m/Y H:i') }}</small>
                </div>
                @empty
                <p class="text-muted-glass mb-0">Aucune attribution.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="glass-header"><i class="bi bi-clock-history me-2"></i>Timeline du véhicule</div>
    <div class="glass-body">
        @forelse($timeline as $event)
        <div class="timeline-item d-flex gap-3 mb-3 pb-3 border-bottom border-secondary border-opacity-25">
            <div class="timeline-icon">
                @switch($event['type'])
                    @case('maintenance')<i class="bi bi-tools text-warning"></i>@break
                    @case('odometer')<i class="bi bi-speedometer2 text-info"></i>@break
                    @case('assignment')<i class="bi bi-person-check text-primary"></i>@break
                    @case('document')<i class="bi bi-file-earmark text-secondary"></i>@break
                    @case('fuel')<i class="bi bi-fuel-pump text-success"></i>@break
                    @default<i class="bi bi-circle"></i>
                @endswitch
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <strong class="text-white">{{ $event['title'] }}</strong>
                    @if($event['certified'])
                        <span class="badge badge-glass-success"><i class="bi bi-shield-check"></i> Certifié</span>
                    @endif
                </div>
                <small class="text-muted-glass">{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y H:i') }}</small>
                @if($event['description'])<p class="small text-muted-glass mb-0 mt-1">{{ $event['description'] }}</p>@endif
                @if($event['hash'])<code class="small d-block mt-1">{{ substr($event['hash'], 0, 24) }}...</code>@endif
            </div>
        </div>
        @empty
        <p class="text-muted-glass mb-0 text-center py-3">Aucun événement enregistré.</p>
        @endforelse
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('history.index') }}" class="btn glass-btn-outline"><i class="bi bi-arrow-left me-1"></i>Retour à l'historique</a>
    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn glass-btn-outline ms-2"><i class="bi bi-truck me-1"></i>Fiche véhicule</a>
</div>
@endsection
