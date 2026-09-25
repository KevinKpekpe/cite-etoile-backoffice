<x-layouts.app title="Nouvel utilisateur">
    <div class="form-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion des accès</p><h1 class="resource-heading__title">Créer un utilisateur</h1><p class="resource-heading__description">Créez un compte interne et attribuez-lui son rôle opérationnel.</p></div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary resource-button">Retour aux utilisateurs</a>
        </header>
        <form method="POST" action="{{ route('users.store') }}" class="form-page__content">
            @csrf
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Identité et accès</h2><p>Coordonnées professionnelles et niveau d’autorisation.</p></div></div>
                <div class="form-section__body"><x-user-form :roles="$roles" /></div>
            </section>
            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button class="btn btn-app-primary resource-button" type="submit">Créer l’utilisateur</button>
            </div>
        </form>
    </div>
</x-layouts.app>
