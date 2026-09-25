<x-layouts.app :title="$neighborhood->exists ? 'Modifier le quartier' : 'Nouveau quartier'">
    @php($statusLabels = ['planned' => 'Planifié', 'active' => 'Actif', 'commercializable' => 'Commercialisable', 'completed' => 'Achevé', 'suspended' => 'Suspendu'])
    <div class="form-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">{{ $neighborhood->exists ? 'Modifier le quartier' : 'Créer un quartier' }}</h1><p class="resource-heading__description">Définissez l’identité et l’état opérationnel de cette zone.</p></div>
            <a href="{{ route('neighborhoods.index') }}" class="btn btn-outline">Retour aux quartiers</a>
        </header>
        <form method="POST" action="{{ $neighborhood->exists ? route('neighborhoods.update', $neighborhood) : route('neighborhoods.store') }}" class="form-page__content">
            @csrf
            @if($neighborhood->exists) @method('PUT') @endif
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Informations du quartier</h2><p>Les codes permettent d’identifier rapidement les zones dans les tableaux.</p></div></div>
                <div class="form-section__body">
                    <div class="form-grid">
                        @if($neighborhood->exists)<x-auth-input name="code" label="Code" :value="old('code', $neighborhood->code)" readonly />@endif
                        <x-auth-input name="name" label="Nom" :value="old('name', $neighborhood->name)" required />
                        <label class="form-field"><span class="form-field__label">Statut <span class="form-required">*</span></span><select name="status" class="form-select">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('status', $neighborhood->status) === $value)>{{ $label }}</option>@endforeach</select>@error('status')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                        <label class="form-field form-grid__wide"><span class="form-field__label">Description</span><textarea name="description" rows="4" class="form-control">{{ old('description', $neighborhood->description) }}</textarea>@error('description')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    </div>
                </div>
            </section>
            <div class="form-actions"><a href="{{ route('neighborhoods.index') }}" class="btn btn-outline">Annuler</a><button class="btn btn-primary" type="submit">{{ $neighborhood->exists ? 'Enregistrer les modifications' : 'Créer le quartier' }}</button></div>
        </form>
    </div>
</x-layouts.app>
