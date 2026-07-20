@extends('layouts.app')

@section('title', 'Modifier chauffeur')

@section('content')
<h1 class="page-title mb-4"><i class="bi bi-pencil me-2"></i>Modifier {{ $driver->name }}</h1>

<div class="glass-card">
    <div class="glass-body">
        <form method="POST" action="{{ route('drivers.update', $driver) }}">@csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nom complet</label><input type="text" name="name" class="form-control glass-input" value="{{ old('name', $driver->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control glass-input" value="{{ old('email', $driver->email) }}" required></div>
                <div class="col-md-6"><label class="form-label">Nouveau mot de passe (optionnel)</label><input type="password" name="password" class="form-control glass-input"></div>
                <div class="col-md-6">
                    <label class="form-label">Wallet Web3 <span class="text-muted-glass">(optionnel à la création)</span></label>
                    <input type="text" name="wallet_address" class="form-control glass-input font-monospace @error('wallet_address') is-invalid @enderror" placeholder="0x + 40 caractères hex" value="{{ old('wallet_address', $driver->wallet_address) }}">
                    <small class="text-muted-glass">Adresse Ethereum du chauffeur — requise pour signer les attributions digitales.</small>
                    @error('wallet_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <button type="button" class="btn btn-sm glass-btn-outline mt-2" onclick="document.querySelector('[name=wallet_address]').value='{{ \App\Support\DemoWallets::DRIVER }}'">
                        <i class="bi bi-lightning me-1"></i>Utiliser l'adresse démo
                    </button>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $driver->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Chauffeur actif</label>
                    </div>
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
