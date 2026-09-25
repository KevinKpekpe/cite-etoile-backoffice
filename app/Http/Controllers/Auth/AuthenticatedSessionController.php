<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($request->throttleKey(), 60);
            throw ValidationException::withMessages(['email' => __('Identifiants incorrects ou compte indisponible.')]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->hasTwoFactorAuthenticationEnabled()) {
            $request->session()->put('auth.two_factor_pending', true);

            return redirect()->route('two-factor.challenge');
        }

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended($user->hasRole('customer') ? route('portal.dashboard') : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
