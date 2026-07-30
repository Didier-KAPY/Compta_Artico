<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $allowedRoles = [];

        foreach ($roles as $roleList) {
            foreach (explode(',', $roleList) as $role) {
                $role = trim($role);

                if ($role !== '') {
                    $allowedRoles[] = $role;
                }
            }
        }

        if (! $user->hasRole($allowedRoles)) {
            abort(403);
        }

        return $next($request);
    }
}
