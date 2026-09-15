<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use App\Security\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    public function setup(Request $request, Totp $totp): View
    {
        $this->ensureSensitiveAccount($request);
        $secret = $request->session()->get('auth.two_factor_setup_secret', fn () => $totp->generateSecret());
        $request->session()->put('auth.two_factor_setup_secret', $secret);

        return view('auth.two-factor-setup', ['secret' => $secret]);
    }

    public function enable(TwoFactorChallengeRequest $request, Totp $totp): RedirectResponse
    {
        $this->ensureSensitiveAccount($request);
        $secret = (string) $request->session()->get('auth.two_factor_setup_secret');

        if ($secret === '' || ! $totp->verify($secret, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => __('Le code de vérification est incorrect.')]);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::lower(Str::random(10).'-'.Str::random(10)))->all();
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(Hash::make(...), $recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('auth.two_factor_setup_secret');

        return redirect()->route('two-factor.setup')->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->ensureSensitiveAccount($request);
        $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();

        return redirect()->route('two-factor.setup')->with('status', __('Authentification à deux facteurs désactivée.'));
    }

    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function verify(TwoFactorChallengeRequest $request, Totp $totp): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $code = $request->string('code')->trim()->toString();
        $valid = $user->two_factor_secret !== null && $totp->verify($user->two_factor_secret, $code);
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];

        if (! $valid) {
            foreach ($recoveryCodes as $index => $recoveryCode) {
                if (Hash::check($code, $recoveryCode)) {
                    unset($recoveryCodes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();
                    $valid = true;
                    break;
                }
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages(['code' => __('Le code est incorrect.')]);
        }

        $request->session()->forget('auth.two_factor_pending');
        $request->session()->regenerate();

        return redirect()->intended($user->hasRole('customer') ? route('portal.dashboard') : route('dashboard'));
    }

    private function ensureSensitiveAccount(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasRole('super_admin'), 403);
    }
}
