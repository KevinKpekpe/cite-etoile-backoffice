<?php

namespace App\Services;

use App\Models\User;

class UserCredentialsMarkdownService
{
    /**
     * Generate a plain-text formatted access sheet for the given user.
     */
    public function generate(User $user, string $temporaryPassword, ?string $roleLabel = null): string
    {
        $roleName = $roleLabel ?? ($user->roles->first()?->name ?? 'customer');
        $loginUrl = url('/login');
        $dateStr = now()->format('d/m/Y à H:i');

        return <<<CREDENTIALS
FICHE D'ACCES — CITE ETOILE DU MONDE
Generee le {$dateStr}

---

INFORMATIONS DU COMPTE

Nom complet    : {$user->first_name} {$user->last_name}
Email          : {$user->email}
Telephone      : {$user->phone}
Role           : {$roleName}

IDENTIFIANTS DE CONNEXION

URL de connexion  : {$loginUrl}
Mot de passe      : {$temporaryPassword}

---

IMPORTANT — Ce mot de passe est temporaire. Lors de votre premiere connexion,
le systeme vous demandera de le remplacer par un mot de passe personnel.
Conservez ce document en lieu sur et ne le transmettez pas par voie non securisee.

CREDENTIALS;
    }
}
