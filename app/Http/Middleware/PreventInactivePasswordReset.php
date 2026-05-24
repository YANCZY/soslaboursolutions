<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class PreventInactivePasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('password.email')) {
            $email = $request->input('email');

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user && $user->status !== 'active') {
                return back()->withErrors([
                    'email' => __('This account is deactivated. Please contact an administrator.'),
                ]);
            }
        }

        return $next($request);
    }
}
