<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin') ?? $request->user();

        if (! $user || ! $user->hasAnyRole([
            RoleSlug::Moderator->value,
            RoleSlug::Editor->value,
            RoleSlug::SuperAdmin->value,
        ])) {
            abort(403, 'You are not authorized to access the admin panel.');
        }

        return $next($request);
    }
}
