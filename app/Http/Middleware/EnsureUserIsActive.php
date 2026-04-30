<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if($user && $user->status !== 'active') {
           Auth::guard('web')->logout();

           $request->session()->invalidate();
           $request->session()->regenerateToken();

           $message = __('Your account has been deactivated. Please contact administrator if this is a mistake.');
            if ($request->expectsJson()) {
                $request->session()->flash('status', $message);

                return response()->json([
                    'message' => $message,
                ], 403);
            }
           return redirect()
            ->route('login')
            ->withErrors([
                'email' => $message,
            ]);
        }

        return $next($request);
    }


}
