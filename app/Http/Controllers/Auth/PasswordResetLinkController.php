<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __('Un lien de réinitialisation a été envoyé à votre adresse e-mail.'));
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Veuillez patienter avant de demander un nouveau lien (une demande par minute).')]);
        }

        // INVALID_USER: silently succeed to not reveal whether the account exists.
        return back()->with('status', __('Si ce compte existe, un lien de réinitialisation a été envoyé.'));
    }
}
