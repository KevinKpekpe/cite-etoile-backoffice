<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Security\Totp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    public function setup(Request $request, Totp $totp): View
    {
        $this->ensurePortalCustomer($request);

        $secret = $request->session()->get('auth.portal_two_factor_setup_secret', fn () => $totp->generateSecret());
        $request->session()->put('auth.portal_two_factor_setup_secret', $secret);
        $otpauthUri = $totp->otpauthUri($secret, $request->user()->email, config('app.name'));

        return view('portal.settings.two-factor-setup', [
            'secret' => $secret,
            'otpauthUri' => $otpauthUri,
        ]);
    }

    public function enable(TwoFactorChallengeRequest $request, Totp $totp): RedirectResponse
    {
        $this->ensurePortalCustomer($request);

        $secret = (string) $request->session()->get('auth.portal_two_factor_setup_secret');

        if ($secret === '' || ! $totp->verify($secret, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid_code')]);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(10).'-'.Str::random(10)))
            ->all();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(Hash::make(...), $recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('auth.portal_two_factor_setup_secret');

        return redirect()
            ->route('portal.two-factor.setup')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->ensurePortalCustomer($request);

        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()
            ->route('portal.settings.index')
            ->with('status', __('auth.two_factor_disabled'));
    }

    private function ensurePortalCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole('customer'), 403);
    }
}
