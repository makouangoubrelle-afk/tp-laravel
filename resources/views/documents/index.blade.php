@extends('layouts.app')

@section('title', 'Documents administratifs')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-folder2-open me-2"></i>Centre documentaire</h1>
        <p class="page-subtitle mb-0">Assurances, cartes grises et pièces administratives centralisées</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="glass-card glass-stat"><h6><i class="bi bi-files me-1"></i>Total documents</h6><h2>{{ $stats['total'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-success"><h6><i class="bi bi-shield-check me-1"></i>Assurances</h6><h2>{{ $stats['insurance'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat"><h6><i class="bi bi-card-heading me-1"></i>Cartes grises</h6><h2>{{ $stats['registration'] }}</h2></div></div>
    <div class="col-md-3"><div class="glass-card glass-stat stat-danger"><h6><i class="bi bi-exclamation-triangle me-1"></i>Expirent bientôt</h6><h2>{{ $stats['expiring'] }}</h2></div></div>
</div>

<div class="glass-card doc-filter-bar mb-3">
    <div class="glass-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2" role="tablist" aria-label="Filtrer les documents">
            <a href="{{ route('documents.index') }}"
               class="doc-filter-btn btn btn-sm {{ !request('type') ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="">
                Tous
            </a>
            <a href="{{ route('documents.index', ['type' => 'registration']) }}"
               class="doc-filter-btn btn btn-sm {{ request('type') === 'registration' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="registration">
                <i class="bi bi-card-heading me-1"></i>Cartes grises
            </a>
            <a href="{{ route('documents.index', ['type' => 'insurance']) }}"
               class="doc-filter-btn btn btn-sm {{ request('type') === 'insurance' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="insurance">
                <i class="bi bi-shield-check me-1"></i>Assurances
            </a>
            <a href="{{ route('documents.index', ['type' => 'invoice']) }}"
               class="doc-filter-btn btn btn-sm {{ request('type') === 'invoice' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="invoice">
                <i class="bi bi-receipt me-1"></i>Factures
            </a>
            <a href="{{ route('documents.index', ['type' => 'contract']) }}"
               class="doc-filter-btn btn btn-sm {{ request('type') === 'contract' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="contract">
                <i class="bi bi-file-earmark-text me-1"></i>Contrats
            </a>
            <a href="{{ route('documents.index', ['type' => 'inspection']) }}"
               class="doc-filter-btn btn btn-sm {{ request('type') === 'inspection' ? 'glass-btn active' : 'glass-btn-outline' }}"
               data-type="inspection">
                <i class="bi bi-clipboard-check me-1"></i>CT
            </a>
        </div>
        @if(request('type'))
            <div class="mt-2 small text-muted-glass">
                <i class="bi bi-funnel me-1"></i>Filtre actif :
                <strong class="text-white">{{ match(request('type')) {
                    'registration' => 'Cartes grises',
                    'insurance' => 'Assurances',
                    'invoice' => 'Factures',
                    'contract' => 'Contrats',
                    'inspection' => 'Contrôle technique',
                    default => request('type'),
                } }}</strong>
                — {{ $documents->total() }} document(s)
                <a href="{{ route('documents.index') }}" class="ms-2 text-decoration-none" style="color: var(--emmaus-glow);">Réinitialiser</a>
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="row g-3" id="documentsGrid">
            @forelse($documents as $doc)
            <div class="col-md-6 doc-item-col" data-doc-type="{{ $doc->type }}">
                <div class="glass-card doc-card h-100">
                    <div class="glass-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="doc-icon"><i class="bi {{ $doc->typeIcon() }}"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="text-white mb-1">{{ $doc->title }}</h6>
                                <span class="badge badge-glass-info mb-2">{{ $doc->typeLabel() }}</span>
                                <div class="small text-muted-glass">
                                    <div><i class="bi bi-truck me-1"></i>{{ $doc->vehicle->plate_number }}</div>
                                    @if($doc->expiry_date)
                                        <div class="{{ $doc->expiry_date->isPast() ? 'text-danger' : '' }}">
                                            <i class="bi bi-calendar-event me-1"></i>Expire le {{ $doc->expiry_date->format('d/m/Y') }}
                                        </div>
                                    @endif
                                    <div><i class="bi bi-fingerprint me-1"></i>{{ substr($doc->checksum, 0, 12) }}...</div>
                                </div>
                                <div class="d-flex gap-1 mt-2">
                                    <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm glass-btn-outline"><i class="bi bi-download"></i></a>
                                    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
                                    <form action="{{ route('documents.verify', $doc) }}" method="POST">@csrf
                                        <button class="btn btn-sm glass-btn-outline" title="Vérifier intégrité"><i class="bi bi-shield-check"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12"><div class="glass-card"><div class="glass-body text-center text-muted-glass py-5">Aucun document archivé.</div></div></div>
            @endforelse
        </div>
        <div class="mt-3">{{ $documents->withQueryString()->links() }}</div>
    </div>

    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
    <div class="col-lg-4">
        <div class="glass-card doc-upload-panel">
            <div class="glass-header"><i class="bi bi-cloud-upload me-2"></i>Ajouter un document</div>
            <div class="glass-body">
                <form method="POST" action="{{ route('documents.store.central') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Véhicule</label>
                        <select name="vehicle_id" class="form-select glass-select" required>
                            @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select glass-select" required>
                            <option value="registration">Carte grise</option>
                            <option value="insurance">Assurance</option>
                            <option value="invoice">Facture</option>
                            <option value="contract">Contrat</option>
                            <option value="inspection">Contrôle technique</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" name="title" class="form-control glass-input" placeholder="Ex: Assurance 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier (PDF, JPG...)</label>
                        <input type="file" name="file" class="form-control glass-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date d'expiration</label>
                        <input type="date" name="expiry_date" class="form-control glass-input">
                    </div>
                    <button type="submit" class="btn glass-btn w-100"><i class="bi bi-archive me-1"></i>Archiver</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
