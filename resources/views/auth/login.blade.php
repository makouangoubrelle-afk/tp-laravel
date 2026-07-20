@extends('layouts.app')

@section('title', 'Connexion - AutoChain Emmaus')

@section('content')
<div class="login-container">
    <div class="login-glass glass-strong">
        <div class="login-logo"><i class="bi bi-shield-lock-fill text-white"></i></div>
        <h1 class="login-title">AutoChain Emmaus</h1>
        <p class="login-subtitle"><i class="bi bi-link-45deg me-1"></i>Gestion de flotte blockchain certifiée</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
                <input type="email" id="loginEmail" name="email" class="form-control glass-input" value="{{ old('email') }}" placeholder="admin@emmaus.fr" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-key me-1"></i>Mot de passe</label>
                <input type="password" id="loginPassword" name="password" class="form-control glass-input" placeholder="••••••••" required>
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Se souvenir de moi</label>
            </div>
            <button type="submit" class="btn glass-btn w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>

        <div class="demo-chip mt-4">
            <strong>Comptes de test :</strong><br>
            Super Admin : <code>admin@emmaus.fr</code> / <code>password</code><br>
            Gestionnaire : <code>gestionnaire@emmaus.fr</code> / <code>password</code>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button type="button" class="btn btn-sm glass-btn-outline demo-login"
                        data-email="gestionnaire@emmaus.fr">Utiliser Gestionnaire</button>
                <button type="button" class="btn btn-sm glass-btn-outline demo-login"
                        data-email="admin@emmaus.fr">Utiliser Admin</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.demo-login').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('loginEmail').value = button.dataset.email;
        document.getElementById('loginPassword').value = 'password';
        document.getElementById('loginPassword').focus();
    });
});
</script>
@endpush
