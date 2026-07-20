@extends('layouts.app')

@section('title', 'Historique certifié')

@section('content')
<div class="mb-4">
    <h1 class="page-title"><i class="bi bi-shield-lock me-2"></i>Historique certifié</h1>
    <p class="page-subtitle">Traçabilité blockchain du kilométrage, des entretiens et des attributions</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-card glass-stat text-center">
            <h6>Score global de certification</h6>
            <div class="integrity-score">{{ round($globalScore) }}%</div>
            <div class="integrity-ring" style="--score: {{ $globalScore }}"></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="glass-card h-100">
            <div class="glass-body">
                <h5 class="text-white mb-3"><i class="bi bi-info-circle me-2"></i>Comment ça fonctionne</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="integrity-feature"><i class="bi bi-speedometer2"></i><strong>Kilométrage</strong><p class="small text-muted-glass mb-0">Chaque relevé est horodaté et hashé on-chain. Impossible de modifier après validation.</p></div>
                    </div>
                    <div class="col-md-4">
                        <div class="integrity-feature"><i class="bi bi-tools"></i><strong>Entretien</strong><p class="small text-muted-glass mb-0">Les maintenances sont certifiées par le garagiste avec preuve blockchain.</p></div>
                    </div>
                    <div class="col-md-4">
                        <div class="integrity-feature"><i class="bi bi-person-check"></i><strong>Attributions</strong><p class="small text-muted-glass mb-0">Prise en charge et restitution signées numériquement.</p></div>
                    </div>
                </div>
                @if($tamperProof)
                    <div class="integrity-badge mt-3"><i class="bi bi-shield-check"></i> Aucune altération détectée sur la flotte</div>
                @else
                    <div class="alert alert-glass-danger mt-3 mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Anomalie détectée sur un ou plusieurs enregistrements</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="glass-header"><i class="bi bi-truck me-2"></i>Historique blockchain par véhicule</div>
    <div class="table-responsive">
        <table class="table glass-table mb-0">
            <thead><tr><th>Véhicule</th><th>Score</th><th>Kilométrage</th><th>Maintenances</th><th>Attributions</th><th></th></tr></thead>
            <tbody>
                @foreach($reports as $report)
                <tr>
                    <td>
                        <strong>{{ $report['vehicle']->plate_number }}</strong>
                        <div class="small text-muted-glass">{{ $report['vehicle']->full_name }}</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="score-bar" style="--w: {{ $report['score'] }}%"></div>
                            <span>{{ $report['score'] }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($report['mileage_tamper_proof'])
                            <span class="badge badge-glass-success"><i class="bi bi-check"></i> Certifié</span>
                        @else
                            <span class="badge badge-glass-danger"><i class="bi bi-x"></i> Anomalie</span>
                        @endif
                    </td>
                    <td>{{ $report['maintenances']->where('valid', true)->count() }}/{{ $report['maintenances']->count() }}</td>
                    <td>{{ $report['assignments']->where('valid', true)->count() }}/{{ $report['assignments']->count() }}</td>
                    <td>
                        <a href="{{ route('history.show', $report['vehicle']) }}" class="btn btn-sm glass-btn-outline">Timeline</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
