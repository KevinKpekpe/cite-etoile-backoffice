<x-layouts.app :title="$plot->exists ? 'Modifier la parcelle' : 'Nouvelle parcelle'">
    @php
        $commercialStatuses = ['available' => 'Disponible', 'reserved' => 'Réservée', 'subscribed' => 'Souscrite', 'blocked' => 'Bloquée', 'unavailable' => 'Indisponible'];
        $financialStatuses = ['unpaid' => 'Non payée', 'partially_paid' => 'Partiellement payée', 'paid' => 'Payée'];
        $administrativeStatuses = ['not_started' => 'Non démarré', 'in_progress' => 'En cours', 'validated' => 'Validé', 'allocated' => 'Attribué', 'dispute' => 'Litige'];
    @endphp
    <div class="form-page">
        <header class="resource-heading"><div><p class="app-kicker">Gestion foncière</p><h1 class="resource-heading__title">{{ $plot->exists ? 'Modifier la parcelle' : 'Créer une parcelle' }}</h1><p class="resource-heading__description">Renseignez les références, dimensions et états de suivi de la parcelle.</p></div><a href="{{ $plot->exists ? route('plots.show', $plot) : route('plots.index') }}" class="btn btn-outline-secondary resource-button">Retour</a></header>
        <form method="POST" action="{{ $plot->exists ? route('plots.update', $plot) : route('plots.store') }}" class="form-page__content">
            @csrf @if($plot->exists) @method('PUT') @endif
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Identification et localisation</h2><p>Références uniques et rattachement territorial.</p></div></div>
                <div class="form-section__body"><div class="form-grid"><x-auth-input name="reference" label="Référence unique" :value="old('reference', $plot->reference)" required /><x-auth-input name="plot_number" label="Numéro" :value="old('plot_number', $plot->plot_number)" required /><label class="form-field"><span class="form-field__label">Avenue</span><select name="avenue_id" class="form-select">@foreach($avenues as $avenue)<option value="{{ $avenue->id }}" @selected((string) old('avenue_id', $plot->avenue_id) === (string) $avenue->id)>{{ $avenue->neighborhood->name }} · {{ $avenue->name }}</option>@endforeach</select>@error('avenue_id')<span class="form-field__error">{{ $message }}</span>@enderror</label><x-auth-input name="cadastral_reference" label="Référence cadastrale" :value="old('cadastral_reference', $plot->cadastral_reference)" /></div></div>
            </section>
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">02</span><div><h2>Caractéristiques physiques et financières</h2><p>Dimensions connues et valeur indicative de la parcelle.</p></div></div>
                <div class="form-section__body"><div class="form-grid"><x-auth-input name="surface_area" label="Superficie (m²)" type="number" step="0.01" :value="old('surface_area', $plot->surface_area)" /><x-auth-input name="base_price" label="Prix de base (USD)" type="number" step="0.01" :value="old('base_price', $plot->base_price)" /><x-auth-input name="width" label="Largeur (m)" type="number" step="0.01" :value="old('width', $plot->width)" /><x-auth-input name="length" label="Longueur (m)" type="number" step="0.01" :value="old('length', $plot->length)" /></div></div>
            </section>
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">03</span><div><h2>États de suivi</h2><p>Situation commerciale, financière et administrative.</p></div></div>
                <div class="form-section__body"><div class="form-grid form-grid--three">@foreach(['commercial_status' => ['Statut commercial', $commercialStatuses], 'financial_status' => ['Statut financier', $financialStatuses], 'administrative_status' => ['Statut administratif', $administrativeStatuses]] as $name => [$label, $statuses])<label class="form-field"><span class="form-field__label">{{ $label }}</span><select name="{{ $name }}" class="form-select">@foreach($statuses as $value => $statusLabel)<option value="{{ $value }}" @selected(old($name, $plot->{$name}) === $value)>{{ $statusLabel }}</option>@endforeach</select>@error($name)<span class="form-field__error">{{ $message }}</span>@enderror</label>@endforeach<label class="form-field form-grid__wide"><span class="form-field__label">Notes internes</span><textarea name="notes" rows="4" class="form-control">{{ old('notes', $plot->notes) }}</textarea>@error('notes')<span class="form-field__error">{{ $message }}</span>@enderror</label></div></div>
            </section>
            <div class="form-actions"><a href="{{ $plot->exists ? route('plots.show', $plot) : route('plots.index') }}" class="btn btn-outline-secondary resource-button">Annuler</a><button class="btn btn-app-primary resource-button" type="submit">{{ $plot->exists ? 'Enregistrer les modifications' : 'Créer la parcelle' }}</button></div>
        </form>
    </div>
</x-layouts.app>
