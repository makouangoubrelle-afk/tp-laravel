@extends('layouts.app')

@section('title', 'Compteur certifié')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-speedometer2 me-2"></i>Compteur certifié</h1>
        <p class="page-subtitle mb-0">Relevés kilométriques signés blockchain — modification impossible après validation</p>
    </div>
</div>

<div class="alert alert-glass mb-4">
    <i class="bi bi-lock-fill me-2"></i>
    <strong>Règle de certification :</strong> une fois un relevé validé on-chain, il est verrouillé définitivement. Aucune modification ni suppression n'est autorisée.
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="glass-header"><i class="bi bi-list-check me-2"></i>Relevés certifiés</div>
            <div class="table-responsive">
                <table class="table glass-table mb-0">
                    <thead><tr><th>Date</th><th>Véhicule</th><th>Kilométrage</th><th>Enregistré par</th><th>Statut</th><th>TX Blockchain</th></tr></thead>
                    <tbody>
                        @forelse($readings as $r)
                        <tr>
                            <td>{{ $r->recorded_at->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('vehicles.show', $r->vehicle) }}" class="text-decoration-none">{{ $r->vehicle->plate_number }}</a></td>
                            <td><strong>{{ number_format($r->mileage) }} km</strong></td>
                            <td>{{ $r->recorder?->name ?? '—' }}</td>
                            <td>
                                @if($r->is_locked ?? true)
                                    <span class="badge badge-glass-success"><i class="bi bi-lock-fill"></i> Verrouillé</span>
                                @else
                                    <span class="badge badge-glass-warning">En attente</span>
                                @endif
                            </td>
                            <td><code class="small">{{ $r->blockchain_tx_hash ? substr($r->blockchain_tx_hash, 0, 16).'...' : '—' }}</code></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted-glass py-4">Aucun relevé certifié.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $readings->links() }}</div>
    </div>

    @if(auth()->user()->hasRole(\App\Enums\UserRole::Driver, \App\Enums\UserRole::FleetManager, \App\Enums\UserRole::SuperAdmin) && $vehicles->isNotEmpty())
    <div class="col-lg-4">
        <div class="glass-card sticky-top" style="top: 80px;">
            <div class="glass-header"><i class="bi bi-plus-circle me-2"></i>Enregistrer le kilométrage</div>
            <div class="glass-body">
                <form method="POST" action="{{ route('odometer.store', $vehicles->first() ?? 0) }}" id="odometerForm">
                    @csrf
                    <input type="hidden" name="redirect" value="odometer.index">
                    <div class="mb-3">
                        <label class="form-label">Véhicule</label>
                        <select class="form-select glass-select" id="odometerVehicle" required>
                            @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" data-mileage="{{ $v->current_mileage }}">{{ $v->plate_number }} — {{ number_format($v->current_mileage) }} km</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau kilométrage</label>
                        <input type="number" name="mileage" id="odometerMileage" class="form-control glass-input" min="0" required>
                        <small class="text-muted-glass">Doit être ≥ au relevé actuel</small>
                    </div>
                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-shield-lock me-1"></i>Certifier et verrouiller</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const vehicleSelect = document.getElementById('odometerVehicle');
const mileageInput = document.getElementById('odometerMileage');
function updateOdometerForm() {
    const opt = vehicleSelect?.selectedOptions[0];
    if (!opt) return;
    document.getElementById('odometerForm').action = '/vehicles/' + opt.value + '/odometer';
    mileageInput.min = opt.dataset.mileage;
    mileageInput.placeholder = 'Min. ' + opt.dataset.mileage + ' km';
}
vehicleSelect?.addEventListener('change', updateOdometerForm);
updateOdometerForm();
</script>
@endpush
