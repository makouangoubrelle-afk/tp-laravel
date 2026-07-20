@extends('layouts.app')

@section('title', 'Planning chauffeurs')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="page-title"><i class="bi bi-calendar-week me-2"></i>Planning & emploi du temps</h1>
        <p class="page-subtitle mb-0">Horaires hebdomadaires et heures de mission — semaine du {{ $weekStart->format('d/m/Y') }}</p>
    </div>
    <div class="d-flex gap-2 planning-toolbar flex-wrap">
        <a href="{{ route('planning.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="btn btn-sm glass-btn-outline" title="Semaine précédente">
            <i class="bi bi-chevron-left"></i>
        </a>
        <a href="{{ route('planning.index') }}" class="btn btn-sm glass-btn">Semaine actuelle</a>
        <a href="{{ route('planning.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="btn btn-sm glass-btn-outline" title="Semaine suivante">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

@if($drivers->isEmpty())
<div class="glass-card no-hover-lift">
    <div class="glass-body text-center text-muted-glass py-5">
        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
        Aucun chauffeur enregistré.
    </div>
</div>
@else
<div class="row g-3 planning-drivers-grid">
    @foreach($drivers as $row)
    @php
        $ecart = round($row['mission_hours'] - $row['contract_hours'], 1);
        $url = route('planning.show', ['driver' => $row['driver'], 'week' => $weekStart->toDateString()]);
    @endphp
    <div class="col-sm-6 col-lg-4">
        <a href="{{ $url }}" class="planning-driver-card glass-card no-hover-lift h-100">
            <div class="glass-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="planning-driver-name fw-bold">{{ $row['driver']->name }}</div>
                        <div class="planning-driver-email">{{ $row['driver']->email }}</div>
                        <span class="badge badge-glass-{{ $row['driver']->availabilityColor() }} mt-2">
                            <i class="bi bi-circle-fill me-1"></i>{{ $row['driver']->availabilityLabel() }}
                        </span>
                    </div>
                    <div class="text-end">
                        @unless($row['driver']->is_active)
                            <span class="badge badge-glass-danger mb-1">Inactif</span>
                        @endunless
                        <span class="badge badge-glass-info d-block">{{ $row['slots_count'] }} créneau(x)</span>
                    </div>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-3">
                        <div class="small text-muted-glass">Prévu</div>
                        <strong class="planning-stat-value">{{ $row['contract_hours'] }} h</strong>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted-glass">Missions</div>
                        <strong class="planning-stat-mission">{{ $row['mission_hours'] }} h</strong>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted-glass">Courses</div>
                        <strong class="planning-stat-value">{{ $row['trips_count'] }}</strong>
                    </div>
                    <div class="col-3">
                        <div class="small text-muted-glass">Écart</div>
                        <strong class="planning-stat-ecart" data-positive="{{ $ecart >= 0 ? '1' : '0' }}">
                            @if($row['contract_hours'] > 0)
                                {{ $ecart > 0 ? '+' : '' }}{{ $ecart }} h
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    @if($row['slots_count'] === 0)
                        <span class="badge badge-glass-warning"><i class="bi bi-exclamation-triangle me-1"></i>Horaires à définir</span>
                    @else
                        <span class="badge badge-glass-success"><i class="bi bi-check2 me-1"></i>Planning actif</span>
                    @endif
                    <span class="btn btn-sm glass-btn-outline"><i class="bi bi-calendar3 me-1"></i>Voir le planning</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif
@endsection
