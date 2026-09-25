<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && (bool) $user->must_change_password) {
            if (! $request->routeIs('password.change', 'password.change.store', 'logout')) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}
