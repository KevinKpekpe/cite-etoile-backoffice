<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cité Étoile du Monde' }} — MJIC Immobilier</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            background-color: #f8fafc;
        }
        .auth-hero-side {
            flex: 1.1;
            background: linear-gradient(135deg, #090d16 0%, #171d30 45%, #2a1b3d 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 4rem;
            color: #ffffff;
            overflow: hidden;
        }
        .auth-hero-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(217, 119, 6, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
            pointer-events: none;
        }
        .auth-hero-glass-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        .auth-form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            background-color: #ffffff;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
        }
        .auth-form-container {
            width: 100%;
            max-width: 440px;
        }
        @media (max-width: 991.98px) {
            .auth-hero-side {
                display: none;
            }
            .auth-form-side {
                min-height: 100vh;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-900 bg-slate-50">
    <div class="auth-wrapper">
        {{-- Section Gauche : Illustration / Branding Hero --}}
        <aside class="auth-hero-side">
            <div style="position: relative; z-index: 2;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill bg-amber-500 text-dark font-bold px-3 py-1 text-uppercase" style="letter-spacing: 0.15em; font-size: 0.7rem; background-color: #f59e0b;">
                        MJIC IMMOBILIER SARL
                    </span>
                </div>
                <h1 class="fw-bold display-5 mb-2" style="font-weight: 800; letter-spacing: -0.02em;">
                    Cité Étoile <span style="color: #fbbf24;">du Monde</span>
                </h1>
                <p class="text-slate-300 fs-5 mb-0" style="max-width: 480px; font-weight: 400; color: #cbd5e1;">
                    Système centralisé de gestion du patrimoine foncier, suivi des souscriptions et encaissements.
                </p>
            </div>

            <div class="my-auto py-5" style="position: relative; z-index: 2;">
                <div class="auth-hero-glass-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                            <i class="bi bi-building fs-5"></i>
                        </div>
                        <div>
                            <h3 class="h6 font-bold text-white mb-0">Gestion Foncière d’Exception</h3>
                            <p class="small text-slate-300 mb-0" style="color: #94a3b8;">Suivi en temps réel des lots, parcelles et réservations.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(99, 102, 241, 0.2); color: #818cf8;">
                            <i class="bi bi-file-earmark-text fs-5"></i>
                        </div>
                        <div>
                            <h3 class="h6 font-bold text-white mb-0">Dossiers Souscripteurs & Contrats</h3>
                            <p class="small text-slate-300 mb-0" style="color: #94a3b8;">Plans de paiement échelonnés et génération de reçus PDF.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.2); color: #34d399;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <div>
                            <h3 class="h6 font-bold text-white mb-0">Sécurité & Traçabilité</h3>
                            <p class="small text-slate-300 mb-0" style="color: #94a3b8;">Authentification renforcée et historique d’audit complet.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="position: relative; z-index: 2;" class="d-flex justify-content-between align-items-center text-xs text-slate-400">
                <span>© {{ date('Y') }} MJIC Immobilier SARL. Tous droits réservés.</span>
                <span class="badge bg-dark text-slate-400 border border-secondary px-2 py-1">v1.0 Backoffice</span>
            </div>
        </aside>

        {{-- Section Droite : Formulaire --}}
        <main class="auth-form-side">
            <div class="auth-form-container">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3 rounded-circle shadow-sm" style="width: 54px; height: 54px; background: linear-gradient(135deg, #1e293b, #0f172a); color: #fbbf24;">
                        <i class="bi bi-stars fs-4"></i>
                    </div>
                    <p class="text-xs font-semibold text-uppercase tracking-widest text-muted mb-1">Espace Administration</p>
                    <h2 class="h3 font-bold text-dark mb-1">{{ $title ?? 'Connexion' }}</h2>
                    <p class="text-muted small">Veuillez renseigner vos identifiants de connexion.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 p-3 text-sm">
                        <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
