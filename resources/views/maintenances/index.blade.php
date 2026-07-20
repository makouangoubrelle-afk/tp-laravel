@extends('layouts.app')

@section('title', 'Maintenance')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-wrench-adjustable me-2"></i>Gestion des entretiens</h1>
        <p class="page-subtitle mb-0">Type d'entretien, pièces changées, coût — signature blockchain</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="glass-card glass-stat"><h6>Interventions</h6><h2>{{ $stats['total'] }}</h2></div></div>
    <div class="col-md-4"><div class="glass-card glass-stat stat-success"><h6>Certifiées on-chain</h6><h2>{{ $stats['certified'] }}</h2></div></div>
    <div class="col-md-4"><div class="glass-card glass-stat"><h6>Coût total</h6><h2>{{ number_format($stats['cost'], 0, ',', ' ') }} €</h2></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-list-check me-2"></i>Historique des maintenances</div>
            <div class="table-responsive">
                <table class="table glass-table mb-0">
                    <thead><tr><th>Date</th><th>Véhicule</th><th>Type</th><th>Pièces</th><th>Coût</th><th>Blockchain</th></tr></thead>
                    <tbody>
                        @forelse($maintenances as $m)
                        <tr>
                            <td>{{ $m->service_date->format('d/m/Y') }}</td>
                            <td><a href="{{ route('vehicles.show', $m->vehicle) }}" class="text-decoration-none">{{ $m->vehicle->plate_number }}</a></td>
                            <td>{{ $m->intervention_type }}</td>
                            <td class="small">{{ $m->parts_replaced ? implode(', ', $m->parts_replaced) : '—' }}</td>
                            <td>{{ $m->cost ? number_format($m->cost, 2).' €' : '—' }}</td>
                            <td>
                                @if($m->blockchain_tx_hash)
                                    <span class="badge badge-glass-success" title="{{ $m->blockchain_tx_hash }}"><i class="bi bi-shield-check"></i> Certifié</span>
                                @else
                                    <span class="badge badge-glass-warning">En attente</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted-glass py-4">Aucune maintenance enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $maintenances->links() }}</div>
    </div>

    @if(auth()->user()->hasRole(\App\Enums\UserRole::Mechanic, \App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager) && $vehicles->isNotEmpty())
    <div class="col-lg-4">
        <div class="glass-card sticky-top" style="top: 80px;">
            <div class="glass-header"><i class="bi bi-plus-circle me-2"></i>Enregistrer une maintenance</div>
            <div class="glass-body">
                <form method="POST" action="{{ route('maintenance.store', $vehicles->first() ?? 0) }}" id="maintenanceForm">
                    @csrf
                    <input type="hidden" name="redirect" value="maintenances.index">
                    <div class="mb-3">
                        <label class="form-label">Véhicule</label>
                        <select name="vehicle_id" class="form-select glass-select" id="maintenanceVehicle" required>
                            @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="service_date" class="form-control glass-input" value="{{ date('Y-m-d') }}" required></div>
                    <div class="mb-3"><label class="form-label">Type d'entretien</label><input type="text" name="intervention_type" class="form-control glass-input" placeholder="Vidange, freins..." required></div>
                    <div class="mb-3"><label class="form-label">Pièces changées</label><input type="text" name="parts_replaced" class="form-control glass-input" placeholder="Filtre, plaquettes (séparées par virgule)"></div>
                    <div class="mb-3"><label class="form-label">Kilométrage</label><input type="number" name="mileage_at_service" class="form-control glass-input" min="0" required></div>
                    <div class="mb-3"><label class="form-label">Coût (€)</label><input type="number" name="cost" class="form-control glass-input" min="0" step="0.01"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control glass-input" rows="2"></textarea></div>
                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-shield-lock me-1"></i>Certifier on-chain</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('maintenanceVehicle')?.addEventListener('change', function() {
    const form = document.getElementById('maintenanceForm');
    form.action = '/vehicles/' + this.value + '/maintenance';
});
</script>
@endpush
