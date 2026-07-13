<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAllowedType
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $userType = $user->userType?->user_type_name;

        if (! in_array($userType, $allowedTypes, true)) {
            abort(403);
        }

        return $next($request);
    }
}
