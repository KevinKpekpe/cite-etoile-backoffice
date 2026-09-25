<x-layouts.app :title="'Modifier · '.$user->first_name.' '.$user->last_name">
    <div class="form-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion des accès</p><h1 class="resource-heading__title">Modifier l’utilisateur</h1><p class="resource-heading__description">{{ $user->first_name }} {{ $user->last_name }} · {{ $user->email }}</p></div>
            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary resource-button">Retour à la fiche</a>
        </header>
        <form method="POST" action="{{ route('users.update', $user) }}" class="form-page__content">
            @csrf
            @method('PUT')
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Identité et accès</h2><p>Coordonnées, rôle, statut et informations de connexion.</p></div></div>
                <div class="form-section__body"><x-user-form :roles="$roles" :user="$user" /></div>
            </section>
            <div class="form-actions">
                <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button class="btn btn-app-primary resource-button" type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</x-layouts.app>
