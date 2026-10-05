<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($title ?? 'Connexion') }} — Cité Étoile du Monde</title>
    <script>
        (() => {
            const theme = localStorage.getItem('cite-etoile-theme') ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.bsTheme = theme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <form method="POST" action="{{ route('locale.update') }}" class="mb-3 text-end" aria-label="{{ __('ui.language') }}">
                @csrf
                <label class="visually-hidden" for="guest-language">{{ __('ui.language') }}</label>
                <select id="guest-language" class="form-select form-select-sm d-inline-block w-auto" name="locale" data-auto-submit>
                    <option value="fr" @selected(app()->getLocale() === 'fr')>Français</option>
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                </select>
            </form>
            <div class="auth-brand">
                <span class="brand-icon">CE</span>
                <span class="brand-name">Cité Étoile <small>{{ __("Backoffice") }}</small></span>
            </div>

            <h1 class="auth-title">{{ __($title ?? 'Connexion') }}</h1>
            <p class="auth-subtitle">{{ __('auth.access_description') }}</p>

            @if (session('status'))
                <div class="auth-notice auth-notice--success" role="status">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{ $slot }}

            <footer class="auth-footer">
                {{ __("Cité Étoile du Monde · MJIC Immobilier SARL") }}
            </footer>
        </div>
    </main>
</body>
</html>
