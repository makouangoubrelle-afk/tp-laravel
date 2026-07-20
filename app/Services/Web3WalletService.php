<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Web3WalletService
{
    private ?string $validationError = null;

    public function __construct(private BlockchainService $blockchain) {}

    public function buildLinkMessage(User $user, string $nonce): string
    {
        return implode("\n", [
            'AutoChain Emmaus — Liaison Wallet',
            'Utilisateur: '.$user->email,
            'ID: '.$user->id,
            'Nonce: '.$nonce,
        ]);
    }

    public function issueNonce(Request $request): string
    {
        $nonce = bin2hex(random_bytes(16));
        $now = now()->timestamp;
        $nonces = collect($request->session()->get('wallet_nonces', []))
            ->filter(fn ($expires) => (int) $expires > $now)
            ->all();
        $nonces[$nonce] = now()->addMinutes(10)->timestamp;
        $request->session()->put('wallet_nonces', $nonces);

        return $nonce;
    }

    public function validateLinkRequest(
        Request $request,
        string $walletAddress,
        string $signature,
        string $message,
        string $nonce
    ): bool
    {
        $nonces = $request->session()->get('wallet_nonces', []);
        $expires = $nonces[$nonce] ?? null;

        if (! $nonce || ! $expires || now()->timestamp > $expires) {
            return $this->fail('nonce_missing_or_expired', $request, $walletAddress);
        }

        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $walletAddress)) {
            return $this->fail('invalid_address', $request, $walletAddress);
        }

        if (! preg_match('/^0x[a-fA-F0-9]+$/', $signature) || strlen($signature) < 130) {
            return $this->fail('invalid_signature_format', $request, $walletAddress);
        }

        $expected = $this->buildLinkMessage($request->user(), $nonce);

        if (! hash_equals($expected, $message)) {
            return $this->fail('message_mismatch', $request, $walletAddress);
        }

        try {
            $signer = $this->blockchain->recoverSigner($message, $signature);
        } catch (Throwable $error) {
            Log::warning('Wallet signature recovery failed', [
                'user_id' => $request->user()?->id,
                'error' => $error->getMessage(),
            ]);

            return $this->fail('signature_recovery_failed', $request, $walletAddress);
        } finally {
            // Un nonce ne peut servir qu'une seule fois, même après un échec.
            unset($nonces[$nonce]);
            $request->session()->put('wallet_nonces', $nonces);
        }

        if (! hash_equals(strtolower($walletAddress), $signer)) {
            return $this->fail('signer_mismatch', $request, $walletAddress, $signer);
        }

        return true;
    }

    public function validationError(): ?string
    {
        return $this->validationError;
    }

    public function markVerified(Request $request, string $walletAddress): void
    {
        $request->session()->put('wallet_verified', true);
        $request->session()->put('wallet_address_session', strtolower($walletAddress));
        $request->session()->forget([
            'wallet_nonces',
            'wallet_nonce',
            'wallet_nonce_expires',
        ]);
    }

    public function isVerified(Request $request): bool
    {
        $user = $request->user();

        if (! $user?->wallet_address) {
            return false;
        }

        return $request->session()->get('wallet_verified')
            && strtolower($request->session()->get('wallet_address_session', '')) === strtolower($user->wallet_address);
    }

    public function buildActionMessage(User $user, string $action, array $payload): string
    {
        return implode("\n", [
            'AutoChain Emmaus — Transaction critique',
            'Action: '.$action,
            'Utilisateur: '.$user->email,
            'Données: '.json_encode($payload),
            'Date: '.now()->toIso8601String(),
        ]);
    }

    private function fail(
        string $reason,
        Request $request,
        ?string $walletAddress = null,
        ?string $recoveredAddress = null
    ): bool {
        $this->validationError = $reason;
        Log::warning('Wallet validation rejected', [
            'reason' => $reason,
            'user_id' => $request->user()?->id,
            'wallet' => $walletAddress,
            'recovered_wallet' => $recoveredAddress,
        ]);

        return false;
    }
}
