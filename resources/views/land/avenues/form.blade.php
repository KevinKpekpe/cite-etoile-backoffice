<x-layouts.app :title="$avenue->exists ? 'Modifier l’avenue' : 'Nouvelle avenue'">
    <div class="form-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">{{ $avenue->exists ? 'Modifier l’avenue' : 'Créer une avenue' }}</h1><p class="resource-heading__description">Rattachez l’avenue à son quartier et renseignez son état opérationnel.</p></div>
            <a href="{{ route('avenues.index') }}" class="btn btn-outline">Retour aux avenues</a>
        </header>
        <form method="POST" action="{{ $avenue->exists ? route('avenues.update', $avenue) : route('avenues.store') }}" class="form-page__content">
            @csrf
            @if($avenue->exists) @method('PUT') @endif
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Informations de l’avenue</h2><p>Le rattachement au quartier détermine l’organisation des parcelles.</p></div></div>
                <div class="form-section__body">
                    <div class="form-grid">
                        <label class="form-field"><span class="form-field__label">Quartier <span class="form-required">*</span></span><select name="neighborhood_id" class="form-select">@foreach($neighborhoods as $item)<option value="{{ $item->id }}" @selected((string) old('neighborhood_id', $avenue->neighborhood_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select>@error('neighborhood_id')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                        <label class="form-field"><span class="form-field__label">Statut <span class="form-required">*</span></span><select name="status" class="form-select">@foreach(['planned' => 'Planifiée', 'active' => 'Active', 'suspended' => 'Suspendue'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $avenue->status) === $value)>{{ $label }}</option>@endforeach</select>@error('status')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                        @if($avenue->exists)<x-auth-input name="code" label="Code" :value="old('code', $avenue->code)" readonly />@endif
                        <x-auth-input name="name" label="Nom" :value="old('name', $avenue->name)" required />
                        <label class="form-field form-grid__wide"><span class="form-field__label">Description</span><textarea name="description" rows="4" class="form-control">{{ old('description', $avenue->description) }}</textarea>@error('description')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                    </div>
                </div>
            </section>
            <div class="form-actions"><a href="{{ route('avenues.index') }}" class="btn btn-outline">Annuler</a><button class="btn btn-primary" type="submit">{{ $avenue->exists ? 'Enregistrer les modifications' : 'Créer l’avenue' }}</button></div>
        </form>
    </div>
</x-layouts.app>
