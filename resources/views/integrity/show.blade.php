@extends('layouts.app')

@section('title', 'Audit — '.$vehicle->plate_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title">{{ $vehicle->full_name }}</h1>
        <p class="page-subtitle mb-0">Audit d'intégrité — {{ $vehicle->plate_number }}</p>
    </div>
    <div class="text-end">
        <div class="integrity-score-sm">{{ $report['score'] }}%</div>
        <small class="text-muted-glass">Score d'intégrité</small>
    </div>
</div>

<div class="row g-3">
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
@endsection
