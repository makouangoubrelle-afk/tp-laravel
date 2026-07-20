@extends('layouts.app')

@section('title', 'Consommation')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-fuel-pump me-2"></i>Suivi de consommation</h1>
        <p class="page-subtitle mb-0">Pleins de carburant et calcul automatique de la consommation moyenne (L/100km)</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="glass-card glass-stat"><h6>Litres total</h6><h2>{{ number_format($stats['total_liters'], 1) }} L</h2></div></div>
    <div class="col-md-4"><div class="glass-card glass-stat"><h6>Coût total</h6><h2>{{ number_format($stats['total_cost'], 0, ',', ' ') }} €</h2></div></div>
    <div class="col-md-4"><div class="glass-card glass-stat stat-success"><h6>Consommation moyenne</h6><h2>{{ $stats['avg_consumption'] }} L/100</h2></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-list-ul me-2"></i>Historique des pleins</div>
            <div class="table-responsive">
                <table class="table glass-table mb-0">
                    <thead><tr><th>Date</th><th>Véhicule</th><th>Litres</th><th>Coût</th><th>Km</th><th>Conso.</th><th>Chauffeur</th></tr></thead>
                    <tbody>
                        @forelse($records as $r)
                        <tr>
                            <td>{{ $r->filled_at->format('d/m/Y') }}</td>
                            <td><a href="{{ route('vehicles.show', $r->vehicle) }}" class="text-decoration-none">{{ $r->vehicle->plate_number }}</a></td>
                            <td>{{ $r->liters }} L</td>
                            <td>{{ number_format($r->cost, 2) }} €</td>
                            <td>{{ number_format($r->mileage_at_fill) }} km</td>
                            <td>
                                @if($r->consumption_avg)
                                    <span class="badge badge-glass-info">{{ $r->consumption_avg }} L/100</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $r->driver?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted-glass py-4">Aucun plein enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $records->links() }}</div>
    </div>

    @if(auth()->user()->hasRole(\App\Enums\UserRole::Driver, \App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin) && $vehicles->isNotEmpty())
    <div class="col-lg-4">
        <div class="glass-card sticky-top" style="top: 80px;">
            <div class="glass-header"><i class="bi bi-plus-circle me-2"></i>Enregistrer un plein</div>
            <div class="glass-body">
                <form method="POST" action="{{ route('fuel.store', $vehicles->first() ?? 0) }}" id="fuelForm">
                    @csrf
                    <input type="hidden" name="redirect" value="fuel.index">
                    <div class="mb-3">
                        <label class="form-label">Véhicule</label>
                        <select class="form-select glass-select" id="fuelVehicle" required>
                            @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Litres</label><input type="number" name="liters" class="form-control glass-input" min="0.1" step="0.1" required></div>
                    <div class="mb-3"><label class="form-label">Coût (€)</label><input type="number" name="cost" class="form-control glass-input" min="0" step="0.01" required></div>
                    <div class="mb-3"><label class="form-label">Kilométrage au plein</label><input type="number" name="mileage_at_fill" class="form-control glass-input" min="0" required></div>
                    <div class="mb-3"><label class="form-label">Référence ticket</label><input type="text" name="slip_reference" class="form-control glass-input" placeholder="Optionnel"></div>
                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-fuel-pump me-1"></i>Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('fuelVehicle')?.addEventListener('change', function() {
    document.getElementById('fuelForm').action = '/vehicles/' + this.value + '/fuel';
});
</script>
@endpush
