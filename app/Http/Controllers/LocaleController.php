<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->user() !== null) {
            $request->user()->update(['locale' => $validated['locale']]);
        }

        App::setLocale($validated['locale']);

        return back();
    }
}
