<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlayerProfileIsComplete
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(Role::Player)) {
            return $next($request);
        }

        if ($user->profile !== null) {
            return $next($request);
        }

        return to_route('profile.edit');
    }
}
