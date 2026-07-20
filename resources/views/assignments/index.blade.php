@extends('layouts.app')

@section('title', 'Attributions digitales')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-person-lines-fill me-2"></i>Attributions digitales</h1>
        <p class="page-subtitle mb-0">Prise en charge et restitution certifiées des véhicules aux chauffeurs</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-card glass-stat stat-warning">
            <h6><i class="bi bi-hourglass-split me-1"></i>En attente de prise en charge</h6>
            <h2>{{ $active->where('status', 'pending_pickup')->count() }}</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card glass-stat stat-success">
            <h6><i class="bi bi-signpost-split me-1"></i>Missions en cours</h6>
            <h2>{{ $active->where('status', 'active')->count() }}</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card glass-stat">
            <h6><i class="bi bi-check2-all me-1"></i>Historique récent</h6>
            <h2>{{ $history->count() }}</h2>
        </div>
    </div>
</div>

@if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager) && $availableVehicles->isNotEmpty())
<div class="glass-card mb-4">
    <div class="glass-header"><i class="bi bi-plus-circle me-2"></i>Nouvelle attribution</div>
    <div class="glass-body">
        <form method="POST" action="{{ route('assignments.store', $availableVehicles->first()) }}" id="assignForm">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Véhicule disponible</label>
                    <select name="vehicle_select" class="form-select glass-select" id="vehicleSelect" onchange="updateAssignAction()">
                        @foreach($availableVehicles as $v)
                            <option value="{{ $v->id }}" data-mileage="{{ $v->current_mileage }}">{{ $v->plate_number }} — {{ $v->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chauffeur</label>
                    <select name="driver_id" class="form-select glass-select @error('driver_id') is-invalid @enderror" required>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" @selected(old('driver_id') == $d->id)>
                                {{ $d->name }}
                                @if($d->wallet_address) — wallet OK @else — ⚠ sans wallet @endif
                            </option>
                        @endforeach
                    </select>
                    @error('driver_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Km départ</label>
                    <input type="number" name="start_mileage" id="startMileage" class="form-control glass-input" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Notes mission</label>
                    <input type="text" name="mission_notes" class="form-control glass-input" placeholder="Destination, objectif...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-send"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<h5 class="text-white mb-3"><i class="bi bi-lightning me-2"></i>Missions actives</h5>
<div class="row g-3 mb-4">
    @forelse($active as $assignment)
    <div class="col-md-6 col-lg-4">
        <div class="glass-card assignment-card h-100">
            <div class="glass-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-0 text-white">{{ $assignment->vehicle->plate_number }}</h5>
                        <small class="text-muted-glass">{{ $assignment->vehicle->full_name }}</small>
                    </div>
                    <span class="badge badge-glass-{{ $assignment->statusColor() }}">{{ $assignment->statusLabel() }}</span>
                </div>

                <div class="assignment-info mb-3">
                    <div><i class="bi bi-person me-2"></i><strong>{{ $assignment->driver->name }}</strong></div>
                    <div>
                        <i class="bi bi-wallet2 me-2"></i>
                        @if($assignment->driver->wallet_address)
                            <code class="small text-white-50">{{ substr($assignment->driver->wallet_address, 0, 16) }}...</code>
                        @else
                            <span class="badge badge-glass-warning">Wallet manquant</span>
                        @endif
                    </div>
                    <div><i class="bi bi-speedometer me-2"></i>{{ number_format($assignment->start_mileage) }} km</div>
                    <div><i class="bi bi-calendar me-2"></i>{{ $assignment->assigned_at->format('d/m/Y H:i') }}</div>
                    @if($assignment->mission_notes)
                        <div class="mt-2"><i class="bi bi-chat-left-text me-2"></i>{{ $assignment->mission_notes }}</div>
                    @endif
                </div>

                @if($assignment->isPendingPickup())
                    @if(auth()->user()->id === $assignment->driver_id || auth()->user()->hasRole(\App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin))
                    <form method="POST" action="{{ route('assignments.pickup', $assignment) }}" class="digital-form">
                        @csrf
                        <label class="form-label small">Prise en charge digitale</label>
                        <input type="number" name="pickup_mileage" class="form-control glass-input mb-2" value="{{ $assignment->start_mileage }}" min="{{ $assignment->start_mileage }}" required>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="pickup_confirm" value="1" class="form-check-input" id="pickup{{ $assignment->id }}" required>
                            <label class="form-check-label small" for="pickup{{ $assignment->id }}">Je confirme la prise en charge du véhicule</label>
                        </div>
                        <button class="btn glass-btn btn-sm w-100"><i class="bi bi-pen me-1"></i>Signer la prise en charge</button>
                    </form>
                    @endif
                @elseif($assignment->isActive())
                    @if($assignment->pickup_signature_hash)
                        <div class="integrity-badge mb-2"><i class="bi bi-shield-check"></i> Prise en charge signée</div>
                    @endif
                    @if(auth()->user()->id === $assignment->driver_id || auth()->user()->hasRole(\App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin))
                    <form method="POST" action="{{ route('assignments.return', $assignment) }}" class="digital-form">
                        @csrf
                        <label class="form-label small">Restitution digitale</label>
                        <input type="number" name="end_mileage" class="form-control glass-input mb-2" placeholder="Km retour" min="{{ $assignment->start_mileage }}" required>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="return_confirm" value="1" class="form-check-input" id="return{{ $assignment->id }}" required>
                            <label class="form-check-label small" for="return{{ $assignment->id }}">Je confirme la restitution du véhicule</label>
                        </div>
                        <button class="btn glass-btn btn-sm w-100"><i class="bi bi-arrow-return-left me-1"></i>Restituer le véhicule</button>
                    </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12"><div class="glass-card"><div class="glass-body text-muted-glass text-center py-4">Aucune mission active.</div></div></div>
    @endforelse
</div>

@if($history->isNotEmpty())
<h5 class="text-white mb-3"><i class="bi bi-clock-history me-2"></i>Historique récent</h5>
<div class="glass-card">
    <div class="table-responsive">
        <table class="table glass-table mb-0">
            <thead><tr><th>Véhicule</th><th>Chauffeur</th><th>Km départ → retour</th><th>Date</th><th>Blockchain</th></tr></thead>
            <tbody>
                @foreach($history as $h)
                <tr>
                    <td>{{ $h->vehicle->plate_number }}</td>
                    <td>{{ $h->driver->name }}</td>
                    <td>{{ number_format($h->start_mileage) }} → {{ number_format($h->end_mileage) }} km</td>
                    <td>{{ $h->returned_at?->format('d/m/Y') }}</td>
                    <td><code class="small">{{ $h->blockchain_tx_hash ? substr($h->blockchain_tx_hash, 0, 14).'...' : '—' }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function updateAssignAction() {
    const sel = document.getElementById('vehicleSelect');
    const form = document.getElementById('assignForm');
    const opt = sel.options[sel.selectedIndex];
    form.action = '/vehicles/' + sel.value + '/assign';
    document.getElementById('startMileage').value = opt.dataset.mileage;
}
document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('vehicleSelect')) updateAssignAction(); });
</script>
@endpush
