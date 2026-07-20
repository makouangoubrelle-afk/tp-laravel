@extends('layouts.app')

@section('title', 'Nouveau chauffeur')

@section('content')
<h1 class="page-title mb-4"><i class="bi bi-person-plus me-2"></i>Ajouter un chauffeur</h1>

<div class="alert alert-glass mb-3">
    <i class="bi bi-wallet2 me-2"></i>
    Le wallet est <strong>optionnel</strong> : le chauffeur pourra le connecter plus tard via <a href="{{ route('wallet.connect') }}" class="text-decoration-none" style="color: var(--emmaus-glow);">Wallet Web3</a>.
    Pour une démo, adresse valide : <code class="text-white-50">{{ \App\Support\DemoWallets::DRIVER }}</code>
</div>

<div class="glass-card">
    <div class="glass-body">
        <form method="POST" action="{{ route('drivers.store') }}">@csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="name" class="form-control glass-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control glass-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control glass-input @error('password') is-invalid @enderror" required>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Wallet Web3 <span class="text-muted-glass">(optionnel)</span></label>
                    <input type="text" name="wallet_address" class="form-control glass-input font-monospace @error('wallet_address') is-invalid @enderror" placeholder="0x + 40 caractères hex — laisser vide si inconnu" value="{{ old('wallet_address') }}">
                    @error('wallet_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn glass-btn"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                <a href="{{ route('drivers.index') }}" class="btn glass-btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
