<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos identifiants d'accès — Cité Étoile du Monde</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f4f6f8; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding: 32px 16px; }
        .wrapper { max-width: 560px; margin: 0 auto; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .header { background: #0f172a; padding: 28px 32px; }
        .header-logo { color: #f8fafc; font-size: 17px; font-weight: 700; letter-spacing: 0.01em; }
        .header-sub { color: #94a3b8; font-size: 12px; margin-top: 4px; }
        .body { padding: 32px; }
        .salutation { font-size: 15px; margin-bottom: 16px; }
        .intro { color: #475569; font-size: 13.5px; line-height: 1.6; margin-bottom: 24px; }
        .credentials-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px 24px; margin-bottom: 24px; }
        .credentials-box table { width: 100%; border-collapse: collapse; }
        .credentials-box td { padding: 7px 0; vertical-align: top; }
        .credentials-box td:first-child { color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; width: 40%; }
        .credentials-box td:last-child { color: #0f172a; font-size: 13.5px; font-weight: 500; }
        .credentials-box .password-value { background: #0f172a; border-radius: 4px; color: #f1f5f9; font-family: 'Courier New', Courier, monospace; font-size: 15px; font-weight: 700; letter-spacing: 0.08em; padding: 2px 8px; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
        .notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; color: #78350f; font-size: 12.5px; line-height: 1.6; padding: 14px 18px; margin-bottom: 24px; }
        .notice strong { display: block; margin-bottom: 4px; }
        .cta { text-align: center; margin-bottom: 28px; }
        .btn { background: #0f172a; border-radius: 6px; color: #f8fafc; display: inline-block; font-size: 13.5px; font-weight: 600; padding: 12px 28px; text-decoration: none; }
        .footer { border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 11.5px; line-height: 1.6; padding: 20px 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="header-logo">Cité Étoile du Monde</div>
                <div class="header-sub">Notification d'accès utilisateur</div>
            </div>

            <div class="body">
                <p class="salutation">Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

                <p class="intro">
                    Votre compte d'accès a été créé. Veuillez conserver les informations ci-dessous avec précaution.
                    Elles vous seront nécessaires pour vous connecter à la plateforme.
                </p>

                <div class="credentials-box">
                    <table>
                        <tr>
                            <td>{{ __("Identifiant") }}</td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td>{{ __("Mot de passe") }}</td>
                            <td><span class="password-value">{{ $temporaryPassword }}</span></td>
                        </tr>
                        <tr>
                            <td>{{ __("Rôle") }}</td>
                            <td>{{ $roleName ?? ($user->roles->first()?->name ?? 'Client') }}</td>
                        </tr>
                    </table>
                </div>

                <div class="notice">
                    <strong>{{ __("Mot de passe temporaire") }}</strong>
                    Lors de votre première connexion, le système vous demandera de définir un nouveau mot de passe personnel.
                    Ce mot de passe temporaire ne sera valable qu'une seule fois.
                </div>

                <div class="cta">
                    <a href="{{ $loginUrl }}" class="btn">{{ __("Accéder à la plateforme") }}</a>
                </div>

                <hr class="divider">

                <p style="color: #94a3b8; font-size: 11.5px; line-height: 1.6;">
                    Un document récapitulatif contenant vos identifiants est joint à cet e-mail.
                    Si vous rencontrez des difficultés pour vous connecter, veuillez contacter votre responsable de compte.
                </p>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} Cité Étoile du Monde &mdash; Tous droits réservés.<br>
                {{ __("Ce message est généré automatiquement. Merci de ne pas y répondre directement.") }}
            </div>
        </div>
    </div>
</body>
</html>
