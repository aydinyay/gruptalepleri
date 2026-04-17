<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $allowedRoles = collect($roles)
            ->map(fn (string $role): string => strtolower(trim($role)))
            ->filter()
            ->values()
            ->all();

        if (empty($allowedRoles)) {
            return $next($request);
        }

        $userRole = strtolower(trim((string) ($user->role ?? '')));
        abort_unless(in_array($userRole, $allowedRoles, true), 403);

        return $next($request);
    }
}

