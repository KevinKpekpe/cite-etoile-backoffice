<x-layouts.app title="Mon profil">
    <div class="form-page profile-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Compte personnel</p>
                <h1 class="resource-heading__title">Mon profil</h1>
                <p class="resource-heading__description">Mettez à jour vos coordonnées sans modifier vos droits d’accès.</p>
            </div>
            <div class="resource-heading__actions">
                <a href="{{ route('two-factor.setup') }}" class="btn btn-outline">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Sécurité du compte
                </a>
            </div>
        </header>

        <section class="profile-summary">
            <span class="profile-summary__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1)) }}</span>
            <div>
                <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                <span>{{ $user->roles->pluck('name')->map(fn ($role) => str($role)->replace('_', ' ')->title())->join(', ') }}</span>
            </div>
            <span class="status status-{{ $user->status === 'active' ? 'green' : 'red' }}">{{ $user->status === 'active' ? 'Compte actif' : 'Compte suspendu' }}</span>
        </section>

        <form method="POST" action="{{ route('profile.update') }}" class="form-page__content">
            @csrf
            @method('PUT')

            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div><h2>Informations personnelles</h2><p>Ces informations sont utilisées dans votre session et les traces d’audit.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <x-auth-input name="first_name" label="Prénom" :value="old('first_name', $user->first_name)" required />
                        <x-auth-input name="last_name" label="Nom" :value="old('last_name', $user->last_name)" required />
                        <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email', $user->email)" required autocomplete="email" />
                        <x-auth-input name="phone" label="Téléphone" :value="old('phone', $user->phone)" required autocomplete="tel" />
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</x-layouts.app>
