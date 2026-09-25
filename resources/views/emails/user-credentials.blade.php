<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos identifiants d'accès</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background: #0f172a; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; color: #fbbf24; }
        .content { padding: 24px; }
        .box { background: #f1f5f9; border-left: 4px solid #d97706; padding: 16px; margin: 20px 0; border-radius: 4px; }
        .btn { display: inline-block; background: #d97706; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; margin-top: 15px; }
        .markdown-block { background: #0f172a; color: #fde68a; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 13px; white-space: pre-wrap; word-break: break-all; margin-top: 15px; }
        .footer { padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cité Étoile du Monde</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">Vos accès utilisateur ont été créés</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

            <p>Votre compte d'accès a été créé avec succès. Voici vos informations de connexion temporaires :</p>

            <div class="box">
                <p style="margin: 0 0 8px 0;"><strong>Identifiant (Email) :</strong> <code>{{ $user->email }}</code></p>
                <p style="margin: 0 0 8px 0;"><strong>Mot de passe temporaire :</strong> <code style="font-size: 16px; font-weight: bold; color: #0f172a;">{{ $temporaryPassword }}</code></p>
                <p style="margin: 0;"><strong>Rôle :</strong> {{ $roleName ?? ($user->roles->first()?->name ?? 'Client') }}</p>
            </div>

            <p style="color: #b45309; font-weight: 600;">
                ⚠️ <strong>Important :</strong> Lors de votre toute première connexion, le système vous demandera obligatoirement de personnaliser et remplacer ce mot de passe temporaire.
            </p>

            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ $loginUrl }}" class="btn">Se connecter à la plateforme</a>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;" />

            <p style="font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 5px;">Fiche récapitulative au format Markdown :</p>
            <div class="markdown-block">{{ $markdownContent }}</div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Cité Étoile du Monde. Tous droits réservés.<br>
            Ceci est un message automatique, merci de ne pas y répondre directement.
        </div>
    </div>
</body>
</html>
