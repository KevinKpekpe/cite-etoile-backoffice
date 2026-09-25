<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    /**
     * Display the force password change view.
     */
    public function show(Request $request): View
    {
        return view('auth.change-password');
    }

    /**
     * Process the password change.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->string('password')),
            'must_change_password' => false,
        ]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'user.password_changed_initial',
            'entity_type' => get_class($user),
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $targetRoute = $user->hasRole('customer') ? route('portal.dashboard') : route('dashboard');

        return redirect()->intended($targetRoute)->with('status', __('Votre mot de passe a été mis à jour avec succès. Bienvenue !'));
    }
}
