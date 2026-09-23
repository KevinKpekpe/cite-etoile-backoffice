<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Connexion' }} — Cité Étoile du Monde</title>
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
            <div class="auth-brand">
                <span class="brand-icon">CE</span>
                <span class="brand-name">Cité Étoile <small>Backoffice</small></span>
            </div>

            <h1 class="auth-title">{{ $title ?? 'Connexion' }}</h1>
            <p class="auth-subtitle">Accédez à la gestion foncière de MJIC Immobilier.</p>

            @if (session('status'))
                <div class="auth-notice auth-notice--success" role="status">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{ $slot }}

            <footer class="auth-footer">
                Cité Étoile du Monde · MJIC Immobilier SARL
            </footer>
        </div>
    </main>
</body>
</html>
