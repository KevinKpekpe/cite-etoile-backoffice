<x-layouts.app title="Modifier le client">
    <div class="form-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Dossier {{ $customer->customer_number }}</p>
                <h1 class="resource-heading__title">Modifier {{ $customer->first_name }} {{ $customer->last_name }}</h1>
                <p class="resource-heading__description">Mettez à jour les informations personnelles et l’attribution du dossier.</p>
            </div>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary resource-button">Retour au dossier</a>
        </header>

        <form method="POST" action="{{ route('customers.update', $customer) }}" enctype="multipart/form-data" class="form-page__content">
            @csrf
            @method('PUT')
            <section class="form-section">
                <div class="form-section__header">
                    <span class="form-section__number">01</span>
                    <div><h2>Informations du client</h2><p>Identité, coordonnées et suivi commercial.</p></div>
                </div>
                <div class="form-section__body"><x-customer-form :customer="$customer" :agents="$agents" /></div>
            </section>
            <div class="form-actions">
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary resource-button">Annuler</a>
                <button class="btn btn-app-primary resource-button" type="submit">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</x-layouts.app>
