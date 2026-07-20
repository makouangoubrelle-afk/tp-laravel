<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\EthereumAddress;
use App\Services\Web3WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletAuthController extends Controller
{
    public function show(Request $request, Web3WalletService $web3): View
    {
        return view('auth.wallet', [
            'isVerified' => $web3->isVerified($request),
        ]);
    }

    public function nonce(Request $request, Web3WalletService $web3): JsonResponse
    {
        $nonce = $web3->issueNonce($request);
        $message = $web3->buildLinkMessage($request->user(), $nonce);

        return response()->json([
            'nonce' => $nonce,
            'message' => $message,
        ]);
    }

    public function verify(Request $request, Web3WalletService $web3)
    {
        $data = $request->validate([
            'wallet_address' => ['required', 'string', new EthereumAddress],
            'signature' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]+$/', 'min:130'],
            'message' => ['required', 'string'],
            'nonce' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
        ]);

        $address = EthereumAddress::normalize($data['wallet_address']);

        if (! $web3->validateLinkRequest(
            $request,
            $address,
            $data['signature'],
            $data['message'],
            $data['nonce'],
        )) {
            $error = match ($web3->validationError()) {
                'nonce_missing_or_expired' => 'La demande ne correspond plus à votre session. Fermez les autres onglets Wallet, rechargez cette page et réessayez.',
                'message_mismatch' => 'Le message signé ne correspond pas à cette session. Rechargez la page avant de recommencer.',
                'signer_mismatch' => 'Le compte MetaMask a changé pendant la signature. Sélectionnez le même compte pour connecter et signer.',
                'signature_recovery_failed' => 'Le serveur n’a pas pu vérifier la signature MetaMask. Réessayez après avoir rechargé la page.',
                default => 'Signature invalide ou expirée. Rechargez la page et réessayez.',
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $error,
                    'reason' => $web3->validationError(),
                ], 422);
            }

            return back()->with('error', $error);
        }

        $alreadyLinked = $request->user()
            ->newQuery()
            ->where('wallet_address', $address)
            ->whereKeyNot($request->user()->id)
            ->exists();

        if ($alreadyLinked) {
            return response()->json([
                'error' => 'Ce wallet est déjà associé à un autre utilisateur.',
            ], 422);
        }

        $request->user()->update(['wallet_address' => $address]);
        $web3->markVerified($request, $address);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'address' => $address,
                'message' => 'Wallet MetaMask connecté et vérifié.',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Wallet MetaMask connecté : '.$address);
    }

    public function disconnect(Request $request)
    {
        $request->user()->update(['wallet_address' => null]);
        $request->session()->forget([
            'wallet_verified',
            'wallet_address_session',
            'wallet_nonces',
            'wallet_nonce',
            'wallet_nonce_expires',
        ]);

        return redirect()->route('wallet.connect')->with('success', 'Wallet déconnecté.');
    }

    /** Active la session wallet sans MetaMask (mode démo TP) */
    public function activateSession(Request $request, Web3WalletService $web3)
    {
        abort_unless(config('blockchain.allow_demo_wallet'), 403, 'Le mode wallet démo est désactivé.');

        $user = $request->user();

        if (! $user->wallet_address) {
            return back()->with('error', 'Aucune adresse wallet enregistrée. Utilisez le mode manuel ou MetaMask.');
        }

        $web3->markVerified($request, $user->wallet_address);

        return redirect()->route('dashboard')->with('success', 'Session wallet activée pour la démo.');
    }

    public function connectManual(Request $request, Web3WalletService $web3)
    {
        abort_unless(config('blockchain.allow_demo_wallet'), 403, 'La connexion manuelle est désactivée.');

        $data = $request->validate([
            'wallet_address' => ['required', 'string', new EthereumAddress],
        ]);

        $address = EthereumAddress::normalize($data['wallet_address']);
        $request->user()->update(['wallet_address' => $address]);
        $web3->markVerified($request, $address);

        return back()->with('success', 'Wallet enregistré et session activée.');
    }
}
