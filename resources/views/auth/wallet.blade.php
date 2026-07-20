@extends('layouts.app')

@section('title', 'Wallet Web3')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="page-title mb-1"><i class="bi bi-wallet2 me-2"></i>Wallet Web3</h1>
        <p class="page-subtitle">Connexion MetaMask — signature des transactions critiques</p>

        <div class="glass-card mb-4">
            <div class="glass-body text-center py-4">
                @if($isVerified)
                    <div class="wallet-status connected mb-4">
                        <div class="wallet-status-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <h4 class="text-white mb-1">Wallet actif</h4>
                        <code class="wallet-address">{{ auth()->user()->wallet_address }}</code>
                        <p class="text-muted-glass small mt-2 mb-0"><i class="bi bi-shield-check me-1"></i>MetaMask / session vérifiée</p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="btn glass-btn me-2"><i class="bi bi-speedometer2 me-1"></i>Tableau de bord</a>
                    <form method="POST" action="{{ route('wallet.disconnect') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn glass-btn-danger"><i class="bi bi-plug me-1"></i>Déconnecter</button>
                    </form>
                @else
                    <div class="wallet-status warning mb-3">
                        <div class="wallet-status-icon warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <h4 class="text-white mb-1">Connecter MetaMask</h4>
                        <p class="text-muted-glass small mb-0">2 étapes : autoriser le site → signer le message</p>
                    </div>

                    {{-- Guide visuel --}}
                    <div class="text-start mb-4 mx-auto" style="max-width:400px">
                        <div class="metamask-step mb-2">
                            <span class="step-num">1</span>
                            <span>Cliquez <strong>Connecter MetaMask</strong> ci-dessous</span>
                        </div>
                        <div class="metamask-step mb-2">
                            <span class="step-num">2</span>
                            <span>Dans la popup MetaMask → <strong>Connecter</strong> (ou <strong>Next</strong>)</span>
                        </div>
                        <div class="metamask-step">
                            <span class="step-num">3</span>
                            <span>Puis cliquez <strong>Signer</strong> pour valider</span>
                        </div>
                    </div>

                    <div id="metamaskStatus" class="metamask-status-box mb-3" style="display:none"></div>
                    <div class="small text-muted-glass mb-3">
                        Demande bloquée ? Ouvrez MetaMask et validez la notification.
                        Si elle n’apparaît pas : <strong>Sites connectés</strong> → déconnectez
                        <code>127.0.0.1:8002</code>, puis réessayez.
                    </div>

                    <button type="button" class="btn glass-btn btn-lg mb-3" id="btnConnectMetaMask">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/36/MetaMask_Fox.svg" alt="" width="28" class="me-2" style="vertical-align:middle">
                        Connecter MetaMask
                    </button>

                    @if(config('blockchain.allow_demo_wallet'))
                    <form method="POST" action="{{ route('wallet.activate') }}" class="w-100 mx-auto" style="max-width:360px">
                        @csrf
                        <button type="submit" class="btn glass-btn-outline w-100">
                            <i class="bi bi-lightning-fill me-1"></i>Passer sans MetaMask (mode démo)
                        </button>
                    </form>
                    @endif
                @endif
            </div>
        </div>

        <div class="glass-card mb-3">
            <div class="glass-header"><i class="bi bi-shield-lock me-2"></i>Transactions nécessitant le wallet</div>
            <div class="glass-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Attribution digitale véhicule → chauffeur</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Prise en charge et restitution</li>
                    <li class="mb-0"><i class="bi bi-check2 text-success me-2"></i>Relevé kilométrique certifié on-chain</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const status = document.getElementById('metamaskStatus');
    if (status) window.autoChainWallet.bindStatus(status);

    document.getElementById('btnConnectMetaMask')?.addEventListener('click', function() {
        window.autoChainWallet.connectFromButton(this);
    });
});
</script>
@endpush
