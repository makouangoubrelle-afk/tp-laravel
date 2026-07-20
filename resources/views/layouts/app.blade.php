<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AutoChain Emmaus')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/glass.css') }}?v=5" rel="stylesheet">
</head>
<body class="@auth bg-app-page @else bg-login-page @endauth">
<div class="page-wrap">
    @auth
    <nav class="navbar navbar-expand-lg glass-navbar sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-link-45deg me-1"></i> AutoChain <span>Emmaus</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-glass" title="Votre rôle"><i class="bi bi-person-badge me-1"></i>{{ auth()->user()->role->label() }}</span>
                @if(auth()->user()->hasRole(\App\Enums\UserRole::Driver))
                    <a href="{{ route('planning.show', auth()->user()) }}"
                       class="badge badge-glass-{{ auth()->user()->availabilityColor() }} text-decoration-none"
                       title="Modifier ma disponibilité">
                        <i class="bi bi-circle-fill me-1"></i>{{ auth()->user()->availabilityLabel() }}
                    </a>
                @endif

                @php $walletVerified = app(\App\Services\Web3WalletService::class)->isVerified(request()); @endphp

                @if(auth()->user()->wallet_address && $walletVerified)
                    <a href="{{ route('wallet.connect') }}" class="badge badge-glass badge-glass-success font-monospace text-decoration-none wallet-badge" data-wallet-connected title="Wallet vérifié — cliquer pour gérer">
                        <span class="wallet-dot"></span><i class="bi bi-wallet2 me-1"></i>{{ substr(auth()->user()->wallet_address, 0, 8) }}...{{ substr(auth()->user()->wallet_address, -4) }}
                    </a>
                @elseif(auth()->user()->wallet_address)
                    <a href="{{ route('wallet.connect') }}" class="badge badge-glass badge-glass-warning font-monospace text-decoration-none" title="Signer avec MetaMask">
                        <i class="bi bi-exclamation-triangle me-1"></i>Signer wallet
                    </a>
                @else
                    <button type="button" class="btn btn-sm glass-btn" id="navbarConnectWallet" title="Connecter MetaMask">
                        <i class="bi bi-wallet2 me-1"></i>Connecter Wallet
                    </button>
                @endif

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm glass-btn-danger"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</button>
                </form>
            </div>
        </div>
    </nav>
    <div class="container-fluid px-4 py-3">
        <div class="row g-3">
            <aside class="col-md-2">
                <nav class="glass-sidebar nav flex-column">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i>Tableau de bord
                    </a>
                    <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}">
                        <span class="me-2">🚗</span>Véhicules
                    </a>
                    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))
                        <a class="nav-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}" href="{{ route('drivers.index') }}">
                            <span class="me-2">👨‍✈️</span>Chauffeurs
                        </a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('planning.*') ? 'active' : '' }}" href="{{ auth()->user()->hasRole(\App\Enums\UserRole::Driver) ? route('planning.show', auth()->user()) : route('planning.index') }}">
                        <span class="me-2">📅</span>{{ auth()->user()->hasRole(\App\Enums\UserRole::Driver) ? 'Mon planning' : 'Planning' }}
                    </a>
                    <a class="nav-link {{ request()->routeIs('maintenances.*', 'maintenance.*') ? 'active' : '' }}" href="{{ route('maintenances.index') }}">
                        <span class="me-2">🔧</span>Maintenance
                    </a>
                    <a class="nav-link {{ request()->routeIs('fuel.*') ? 'active' : '' }}" href="{{ route('fuel.index') }}">
                        <span class="me-2">⛽</span>Consommation
                    </a>
                    <a class="nav-link {{ request()->routeIs('odometer.*') ? 'active' : '' }}" href="{{ route('odometer.index') }}">
                        <i class="bi bi-speedometer me-2"></i>Compteur certifié
                    </a>
                    <a class="nav-link {{ request()->routeIs('assignments.*') ? 'active' : '' }}" href="{{ route('assignments.index') }}">
                        <i class="bi bi-person-lines-fill me-2"></i>Attributions
                    </a>
                    <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                        <i class="bi bi-folder2-open me-2"></i>Documents
                    </a>
                    <a class="nav-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                        <i class="bi bi-bell me-2"></i>Alertes
                        @php $pendingAlerts = \App\Models\Alert::where('status','pending')->count(); @endphp
                        @if($pendingAlerts > 0)<span class="badge badge-glass-danger ms-1">{{ $pendingAlerts }}</span>@endif
                    </a>
                    <a class="nav-link {{ request()->routeIs('history.*', 'integrity.*') ? 'active' : '' }}" href="{{ route('history.index') }}">
                        <i class="bi bi-shield-lock me-2"></i>Historique certifié
                    </a>
                    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin))
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="bi bi-people me-2"></i>Utilisateurs
                        </a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('wallet.*') ? 'active' : '' }}" href="{{ route('wallet.connect') }}">
                        <i class="bi bi-currency-bitcoin me-2"></i>Wallet Web3
                    </a>
                </nav>
            </aside>
            <main class="col-md-10">
                @if(session('success'))<div class="alert alert-glass-success mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-glass-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}</div>@endif
                @if($errors->any())
                    <div class="alert alert-glass-danger mb-3">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @else
        @if(session('success'))<div class="container mt-3"><div class="alert alert-glass-success">{{ session('success') }}</div></div>@endif
        @if($errors->any())
            <div class="container mt-3"><div class="alert alert-glass-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
        @endif
        @yield('content')
    @endauth
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@auth
<script src="https://cdn.jsdelivr.net/npm/ethers@6.13.2/dist/ethers.umd.min.js"></script>
@php
    $autoChainConfig = [
        'chainId' => config('blockchain.chain_id'),
        'chainName' => config('blockchain.network') === 'polygon-mainnet' ? 'Polygon' : 'Polygon Amoy',
        'rpcUrl' => config('blockchain.public_rpc_url'),
        'explorerUrl' => config('blockchain.explorer_url'),
        'nativeCurrency' => ['name' => 'POL', 'symbol' => 'POL', 'decimals' => 18],
    ];
@endphp
<script>
window.autoChainConfig = @json($autoChainConfig);
</script>
<script src="{{ asset('js/web3-wallet.js') }}?v=9"></script>
<script>
document.getElementById('navbarConnectWallet')?.addEventListener('click', function() {
    window.location.href = '/wallet';
});
</script>
@endauth
@stack('scripts')
</body>
</html>
