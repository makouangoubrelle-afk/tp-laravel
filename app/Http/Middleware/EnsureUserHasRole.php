<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Accès non autorisé.');
        }

        $allowed = collect($roles)->map(fn (string $role) => UserRole::from($role));

        if (! $user->hasRole(...$allowed->all())) {
            abort(403, 'Vous n\'avez pas les permissions nécessaires.');
        }

        return $next($request);
    }
}
