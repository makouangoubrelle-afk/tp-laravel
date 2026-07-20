@extends('layouts.app')

@section('title', 'Nouvel utilisateur')

@section('content')
<h1 class="page-title mb-4"><i class="bi bi-person-plus me-2"></i>Créer un utilisateur</h1>

<div class="glass-card">
    <div class="glass-body">
        <form method="POST" action="{{ route('users.store') }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nom</label><input type="text" name="name" class="form-control glass-input" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control glass-input" required></div>
                <div class="col-md-6"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control glass-input" required></div>
                <div class="col-md-6"><label class="form-label">Rôle</label>
                    <select name="role" class="form-select glass-select" required>@foreach($roles as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach</select>
                </div>
                <div class="col-md-6"><label class="form-label">Wallet (optionnel)</label><input type="text" name="wallet_address" class="form-control glass-input font-monospace" placeholder="0x..."></div>
            </div>
            <button type="submit" class="btn glass-btn mt-4"><i class="bi bi-check-lg me-1"></i>Créer</button>
        </form>
    </div>
</div>
@endsection
