@extends('layouts.app')

@section('title', 'Alertes automatiques')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-bell me-2"></i>Alertes automatiques</h1>
        <p class="page-subtitle mb-0">Réduction des coûts via l'automatisation des rappels (CT, assurance, vidange)</p>
    </div>
    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
    <form method="POST" action="{{ route('alerts.sync') }}">@csrf
        <button class="btn glass-btn"><i class="bi bi-arrow-repeat me-1"></i>Synchroniser</button>
    </form>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="glass-card glass-stat stat-warning"><h6>En attente</h6><h2>{{ $stats['pending'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-danger"><h6>Critiques</h6><h2>{{ $stats['critical'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-danger"><h6>En retard</h6><h2>{{ $stats['overdue'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-success"><h6>Résolues</h6><h2>{{ $stats['resolved'] }}</h2></div></div>
</div>

<div class="glass-card alert-filter-bar mb-3">
    <div class="glass-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2" role="tablist" aria-label="Filtrer les alertes">
            <a href="{{ route('alerts.index') }}"
               class="alert-filter-btn btn btn-sm {{ !request('status') && !request('priority') ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-status="" data-priority="">
                Toutes
            </a>
            <a href="{{ route('alerts.index', ['status' => 'pending']) }}"
               class="alert-filter-btn btn btn-sm {{ request('status') === 'pending' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-status="pending" data-priority="">
                En attente
            </a>
            <a href="{{ route('alerts.index', ['priority' => 'critical']) }}"
               class="alert-filter-btn btn btn-sm {{ request('priority') === 'critical' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-status="" data-priority="critical">
                Critiques
            </a>
        </div>
        <div class="mt-2 small text-muted-glass" id="alertFilterLabel">
            @if(request('status') === 'pending')
                <i class="bi bi-funnel me-1"></i>Filtre actif : <strong class="text-white">En attente</strong>
            @elseif(request('priority') === 'critical')
                <i class="bi bi-funnel me-1"></i>Filtre actif : <strong class="text-white">Critiques</strong>
            @else
                <i class="bi bi-funnel me-1"></i>Affichage : <strong class="text-white">Toutes les alertes</strong>
            @endif
            — <span id="alertFilterCount">{{ $alerts->total() }}</span> alerte(s)
        </div>
    </div>
</div>

<div class="glass-card" id="alertsList">
    @forelse($alerts as $alert)
    <div class="alert-item-row alert-row d-flex justify-content-between align-items-center p-3 {{ !$loop->last ? 'border-bottom border-light border-opacity-10' : '' }}"
         data-status="{{ $alert->status }}" data-priority="{{ $alert->priority }}">
        <div class="d-flex align-items-center gap-3">
            <div class="alert-priority priority-{{ $alert->priority }}">
                @switch($alert->priority)
                    @case('critical') <i class="bi bi-exclamation-octagon"></i> @break
                    @case('high') <i class="bi bi-exclamation-triangle"></i> @break
                    @default <i class="bi bi-info-circle"></i>
                @endswitch
            </div>
            <div>
                <strong class="text-white">{{ $alert->vehicle->plate_number }}</strong>
                <span class="text-muted-glass ms-2">{{ $alert->message }}</span>
                <div class="small text-muted-glass mt-1">
                    <i class="bi bi-calendar me-1"></i>{{ $alert->due_date->format('d/m/Y') }}
                    · Priorité : <span class="text-capitalize">{{ $alert->priority }}</span>
                    · Statut : <span class="text-capitalize">{{ $alert->status === 'pending' ? 'en attente' : 'résolu' }}</span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($alert->due_date->isPast())
                <span class="badge badge-glass-danger">En retard</span>
            @endif
            @if($alert->status === 'pending' && auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
            <form method="POST" action="{{ route('alerts.resolve', $alert) }}">@csrf @method('PATCH')
                <button class="btn btn-sm glass-btn-outline"><i class="bi bi-check-lg"></i> Résolu</button>
            </form>
            @elseif($alert->status === 'resolved')
                <span class="badge badge-glass-success">Résolu</span>
            @endif
        </div>
    </div>
    @empty
    <div class="glass-body text-center text-muted-glass py-5"><i class="bi bi-check2-circle fs-1 d-block mb-2"></i>Aucune alerte pour ce filtre.</div>
    @endforelse
</div>
<div class="mt-3">{{ $alerts->withQueryString()->links() }}</div>
@endsection

@push('scripts')
<script>
(function () {
    const rows = document.querySelectorAll('.alert-item-row');
    const buttons = document.querySelectorAll('.alert-filter-btn');
    const countEl = document.getElementById('alertFilterCount');
    const labelEl = document.getElementById('alertFilterLabel');

    function applyFilter(status, priority, updateUrl) {
        let visible = 0;
        rows.forEach(function (row) {
            const matchStatus = !status || row.dataset.status === status;
            const matchPriority = !priority || row.dataset.priority === priority;
            const show = matchStatus && matchPriority;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) countEl.textContent = visible;

        buttons.forEach(function (btn) {
            const active = btn.dataset.status === (status || '') && btn.dataset.priority === (priority || '');
            btn.classList.toggle('glass-btn', active);
            btn.classList.toggle('glass-btn-outline', !active);
            btn.classList.toggle('active', active);
        });

        if (labelEl) {
            let text = '<i class="bi bi-funnel me-1"></i>';
            if (status === 'pending') {
                text += 'Filtre actif : <strong class="text-white">En attente</strong>';
            } else if (priority === 'critical') {
                text += 'Filtre actif : <strong class="text-white">Critiques</strong>';
            } else {
                text += 'Affichage : <strong class="text-white">Toutes les alertes</strong>';
            }
            text += ' — <span id="alertFilterCount">' + visible + '</span> alerte(s)';
            labelEl.innerHTML = text;
        }

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.search = '';
            if (status) url.searchParams.set('status', status);
            if (priority) url.searchParams.set('priority', priority);
            window.history.replaceState({}, '', url);
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            applyFilter(this.dataset.status || '', this.dataset.priority || '', true);
        });
    });

    const params = new URLSearchParams(window.location.search);
    applyFilter(params.get('status') || '', params.get('priority') || '', false);
})();
</script>
@endpush
