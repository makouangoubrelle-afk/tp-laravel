<?php

namespace App\Http\Middleware;

use App\Services\Web3WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVerifiedWallet
{
    public function handle(Request $request, Closure $next): Response
    {
        $web3 = app(Web3WalletService::class);

        if (! $web3->isVerified($request)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Wallet MetaMask requis. Connectez-vous via Wallet Web3.'], 403);
            }

            return redirect()->route('wallet.connect')
                ->with('error', 'Connectez et signez avec MetaMask pour effectuer cette action critique.');
        }

        return $next($request);
    }
}
