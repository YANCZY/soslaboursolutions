<?php

namespace App\Http\Middleware\Mobile;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated.');

        abort_unless(
            $user->tokenCan('mobile:access'),
            403,
            'This token cannot access the mobile application.',
        );

        if ($user->status !== 'active') {
            $user->currentAccessToken()?->revoke();

            abort(403, 'Your account is inactive. Contact your administrator.');
        }

        abort_unless(
            $user->hasVerifiedEmail(),
            403,
            'Verify your email through the web portal before signing in.',
        );

        return $next($request);
    }
}
