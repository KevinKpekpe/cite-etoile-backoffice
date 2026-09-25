<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $request->validate(['email' => ['required', 'email']]);

        if (! User::where('email', $email)->exists()) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => __('Votre adresse e-mail n\'existe pas dans le système.')]);
        }

        try {
            $status = Password::sendResetLink(['email' => $email]);

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de l\'envoi du mot de passe oublié: '.$e->getMessage());

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => __('Une erreur s\'est produite lors de l\'envoi de l\'e-mail ('.$e->getMessage().'). Veuillez réessayer dans quelques instants.')]);
        }
    }
}
