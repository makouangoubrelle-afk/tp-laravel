@extends('layouts.app')



@section('title', 'Planning — '.$driver->name)



@section('content')

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">

    <div>

        <h1 class="page-title mb-1">{{ $driver->name }}</h1>

        <p class="page-subtitle mb-0">

            Emploi du temps & courses — semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}

        </p>

    </div>

    <div class="d-flex gap-2 flex-wrap planning-toolbar">

        <a href="{{ route('planning.show', ['driver' => $driver, 'week' => $prevWeek]) }}" class="btn btn-sm glass-btn-outline"><i class="bi bi-chevron-left"></i> Semaine préc.</a>

        @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))

        <a href="{{ route('planning.index', ['week' => $weekStart->toDateString()]) }}" class="btn btn-sm glass-btn-outline"><i class="bi bi-people me-1"></i>Tous les chauffeurs</a>

        @endif

        <a href="{{ route('planning.show', ['driver' => $driver, 'week' => $nextWeek]) }}" class="btn btn-sm glass-btn-outline">Semaine suiv. <i class="bi bi-chevron-right"></i></a>

    </div>

</div>



<div class="glass-card no-hover-lift mb-4">
    <div class="glass-body">
        <div class="row align-items-center g-3">
            <div class="col-lg-4">
                <div class="small text-muted-glass mb-1">Disponibilité actuelle</div>
                <span class="badge badge-glass-{{ $driver->availabilityColor() }} fs-6">
                    <i class="bi bi-circle-fill me-1"></i>{{ $driver->availabilityLabel() }}
                </span>
                @if($driver->availability_updated_at)
                    <div class="small text-muted-glass mt-2">
                        Mise à jour le {{ $driver->availability_updated_at->format('d/m/Y à H:i') }}
                    </div>
                @endif
                @if($driver->availability_note)
                    <div class="small text-white mt-1">{{ $driver->availability_note }}</div>
                @endif
            </div>
            @if($canEdit)
            <div class="col-lg-8">
                <form method="POST" action="{{ route('planning.availability.update', $driver) }}" class="row g-2 align-items-end">
                    @csrf @method('PATCH')
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <div class="col-md-4">
                        <label class="form-label">Je suis</label>
                        <select name="availability_status" class="form-select glass-select" required>
                            <option value="available" @selected($driver->availability_status === 'available')>Disponible</option>
                            <option value="occupied" @selected($driver->availability_status === 'occupied')>Occupé</option>
                            <option value="off_duty" @selected($driver->availability_status === 'off_duty')>Hors service</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Précision (optionnel)</label>
                        <input type="text" name="availability_note" class="form-control glass-input"
                               maxlength="255" value="{{ old('availability_note', $driver->availability_note) }}"
                               placeholder="Ex. En course jusqu'à 15h">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn glass-btn w-100">
                            <i class="bi bi-check-lg me-1"></i>Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <div class="col-md-3"><div class="glass-card glass-stat"><h6>Heures prévues</h6><h2>{{ $stats['contract_hours'] }} h</h2></div></div>

    <div class="col-md-3"><div class="glass-card glass-stat stat-success"><h6>Heures missions</h6><h2>{{ $stats['mission_hours'] }} h</h2></div></div>

    <div class="col-md-3"><div class="glass-card glass-stat stat-warning"><h6>Missions</h6><h2>{{ $stats['missions_count'] }}</h2></div></div>

    <div class="col-md-3"><div class="glass-card glass-stat"><h6>Courses déclarées</h6><h2>{{ $stats['trips_count'] }}</h2></div></div>

</div>



<div class="row g-3">

    <div class="col-lg-8">

        <div class="glass-card mb-4 no-hover-lift">

            <div class="glass-header"><i class="bi bi-calendar-week me-2"></i>Grille hebdomadaire</div>

            <div class="glass-body planning-grid">

                @foreach(\App\Models\DriverSchedule::DAYS as $dayNum => $dayName)

                <div class="planning-day">

                    <div class="planning-day-title">{{ $dayName }}</div>

                    @forelse($schedulesByDay[$dayNum] ?? [] as $slot)

                        <div class="planning-slot">

                            <strong>{{ $slot->timeRangeLabel() }}</strong>

                            @if($slot->label)<span class="small text-muted-glass d-block">{{ $slot->label }}</span>@endif

                            @if($canEdit)

                            <form method="POST" action="{{ route('planning.schedules.destroy', $slot) }}" class="mt-1" onsubmit="return confirm('Supprimer ce créneau ?')">

                                @csrf @method('DELETE')

                                <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 small">Supprimer</button>

                            </form>

                            @endif

                        </div>

                    @empty

                        <div class="planning-slot empty text-muted-glass small">Repos / non planifié</div>

                    @endforelse

                </div>

                @endforeach

            </div>

        </div>



        <div class="glass-card mb-4 no-hover-lift">

            <div class="glass-header"><i class="bi bi-signpost-split me-2"></i>Mes courses (semaine)</div>

            <div class="glass-body">

                @forelse($trips as $trip)

                <div class="d-flex justify-content-between align-items-start border-bottom border-light border-opacity-10 py-3 gap-2">

                    <div>

                        <strong class="text-white">{{ $trip->origin }}</strong>

                        <i class="bi bi-arrow-right mx-1 text-muted-glass"></i>

                        <strong class="text-white">{{ $trip->destination }}</strong>

                        <div class="small text-muted-glass mt-1">

                            <i class="bi bi-clock me-1"></i>{{ $trip->trip_at->format('d/m/Y H:i') }}

                            @if($trip->vehicle) · {{ $trip->vehicle->plate_number }} @endif

                            @if($trip->distance_km) · {{ $trip->distance_km }} km @endif

                        </div>

                        @if($trip->notes)<div class="small text-muted-glass">{{ $trip->notes }}</div>@endif

                    </div>

                    @if($canEdit)

                    <form method="POST" action="{{ route('planning.trips.destroy', $trip) }}" onsubmit="return confirm('Supprimer cette course ?')">

                        @csrf @method('DELETE')

                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                        <button type="submit" class="btn btn-sm glass-btn-danger"><i class="bi bi-trash"></i></button>

                    </form>

                    @endif

                </div>

                @empty

                <p class="text-muted-glass mb-0 text-center py-3">Aucune course déclarée cette semaine.</p>

                @endforelse

            </div>

        </div>



        <div class="glass-card no-hover-lift">

            <div class="glass-header"><i class="bi bi-car-front me-2"></i>Missions véhicule (attributions)</div>

            <div class="glass-body">

                @forelse($missions as $mission)

                @php

                    $start = $mission->pickup_confirmed_at ?? $mission->assigned_at;

                    $end = $mission->returned_at ?? now();

                    $hours = round($start->diffInMinutes($end) / 60, 1);

                @endphp

                <div class="d-flex justify-content-between align-items-start border-bottom border-light border-opacity-10 py-3">

                    <div>

                        <strong class="text-white">{{ $mission->vehicle->plate_number }}</strong>

                        <span class="badge badge-glass-{{ $mission->statusColor() }} ms-2">{{ $mission->statusLabel() }}</span>

                        <div class="small text-muted-glass mt-1">

                            <i class="bi bi-clock me-1"></i>{{ $start->format('d/m H:i') }}

                            @if($mission->returned_at) → {{ $end->format('d/m H:i') }} @endif

                            · {{ $hours }} h

                        </div>

                        @if($mission->mission_notes)<div class="small text-muted-glass">{{ $mission->mission_notes }}</div>@endif

                    </div>

                </div>

                @empty

                <p class="text-muted-glass mb-0 text-center py-3">Aucune mission sur cette semaine.</p>

                @endforelse

            </div>

        </div>

    </div>



    @if($canEdit)

    <div class="col-lg-4">

        <div class="glass-card sticky-top planning-form-panel no-hover-lift mb-3">

            <div class="glass-header"><i class="bi bi-plus-circle me-2"></i>Ajouter un créneau horaire</div>

            <div class="glass-body">

                <form method="POST" action="{{ route('planning.schedules.store', $driver) }}">

                    @csrf

                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                    <div class="mb-3">

                        <label class="form-label">Jour</label>

                        <select name="day_of_week" class="form-select glass-select" required>

                            @foreach(\App\Models\DriverSchedule::DAYS as $num => $label)

                                <option value="{{ $num }}">{{ $label }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Début</label>

                        <input type="time" name="start_time" class="form-control glass-input" value="08:00" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Fin</label>

                        <input type="time" name="end_time" class="form-control glass-input" value="12:00" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Libellé (optionnel)</label>

                        <input type="text" name="label" class="form-control glass-input" placeholder="Matin, Après-midi…">

                    </div>

                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-check-lg me-1"></i>Enregistrer le créneau</button>

                </form>

            </div>

        </div>



        <div class="glass-card sticky-top planning-form-panel no-hover-lift">

            <div class="glass-header"><i class="bi bi-geo-alt me-2"></i>Déclarer une course</div>

            <div class="glass-body">

                <form method="POST" action="{{ route('planning.trips.store', $driver) }}">

                    @csrf

                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                    <div class="mb-3">

                        <label class="form-label">Date et heure</label>

                        <input type="datetime-local" name="trip_at" class="form-control glass-input" value="{{ now()->format('Y-m-d\TH:i') }}" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Départ</label>

                        <input type="text" name="origin" class="form-control glass-input" placeholder="Entrepôt Emmaus…" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Destination</label>

                        <input type="text" name="destination" class="form-control glass-input" placeholder="Point de collecte…" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Véhicule (optionnel)</label>

                        <select name="vehicle_id" class="form-select glass-select">

                            <option value="">— Aucun —</option>

                            @foreach($vehicles as $vehicle)

                                <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} — {{ $vehicle->brand }} {{ $vehicle->model }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Distance (km)</label>

                        <input type="number" name="distance_km" class="form-control glass-input" min="1" placeholder="Ex. 45">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Notes (optionnel)</label>

                        <textarea name="notes" class="form-control glass-input" rows="2" placeholder="Détails de la course…"></textarea>

                    </div>

                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-check-lg me-1"></i>Enregistrer la course</button>

                </form>

            </div>

        </div>

    </div>

    @endif

</div>

@endsection

