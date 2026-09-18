<?php

namespace App\Services;

use App\Models\User;

class UserCredentialsMarkdownService
{
    /**
     * Generate a Markdown formatted summary of user credentials and access instructions.
     */
    public function generate(User $user, string $temporaryPassword, ?string $roleLabel = null): string
    {
        $roleName = $roleLabel ?? ($user->roles->first()?->name ?? 'customer');
        $loginUrl = url('/login');
        $dateStr = now()->format('d/m/Y à H:i');

        return <<<MARKDOWN
# 🔑 Fiche d'Accès Utilisateur — Cité Étoile du Monde

> **Document généré le :** {$dateStr}

---

### 👤 Informations du Compte
- **Nom complet :** {$user->first_name} {$user->last_name}
- **Email / Identifiant :** `{$user->email}`
- **Téléphone :** {$user->phone}
- **Rôle attribué :** `{$roleName}`

### 🔐 Identifiants Temporaires
- **URL de Connexion :** [{$loginUrl}]({$loginUrl})
- **Mot de Passe Temporaire :** `{$temporaryPassword}`

---

> ⚠️ **CONSIGNE DE SÉCURITÉ IMPORTANTE**
> Lors de votre toute première connexion à la plateforme, le système vous demandera **obligatoirement** de remplacer ce mot de passe temporaire par un mot de passe personnel sécurisé.

MARKDOWN;
    }
}
