<x-layouts.app :title="$feeType->exists ? 'Modifier un type de frais' : 'Nouveau type de frais'">
    <div class="form-page">
        <header class="resource-heading">
            <div><p class="app-kicker">Gestion financière</p><h1 class="resource-heading__title">{{ $feeType->exists ? 'Modifier un type de frais' : 'Créer un type de frais' }}</h1><p class="resource-heading__description">Le code identifie le type dans les frais générés et ne peut pas être modifié après sa création.</p></div>
            <a href="{{ route('ancillary-fee-types.index') }}" class="btn btn-outline">{{ __("Retour au catalogue") }}</a>
        </header>
        @if($errors->has('fee_type'))<div class="alert alert-danger">{{ $errors->first('fee_type') }}</div>@endif
        <form method="POST" action="{{ $feeType->exists ? route('ancillary-fee-types.update', $feeType) : route('ancillary-fee-types.store') }}" class="form-page__content">
            @csrf
            @if($feeType->exists) @method('PUT') @endif
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>{{ __("Type de frais") }}</h2><p>Définissez son nom et ses montants de référence ou ses tarifs par formule.</p></div></div>
                <div class="form-section__body"><div class="form-grid">
                    @if($feeType->exists)
                        <x-auth-input name="code_display" label="Code" :value="$feeType->code" readonly />
                    @else
                        <x-auth-input name="code" label="Code" :value="old('code')" placeholder="Ex. raccordement_eau" required />
                        @error('code')<span class="form-field__error">{{ $message }}</span>@enderror
                    @endif
                    <x-auth-input name="name" label="Nom du type" :value="old('name', $feeType->name)" required />
                    @if($feeType->code === 'development')
                        @php
                            $developmentOptions = [
                                'cash' => 'Comptant',
                                'one_year' => 'Formule 1 an',
                                'three_years' => 'Formule 3 ans',
                                'five_years' => 'Formule 5 ans',
                                'ten_years' => 'Formule 10 ans',
                            ];
                        @endphp
                        <div class="form-grid__wide">
                            <h3>Tarifs d’aménagement par formule</h3>
                            <p class="resource-heading__description">Ces montants seront appliqués aux nouveaux contrats signés. Les frais déjà générés garderont leurs montants.</p>
                            @foreach($developmentOptions as $option => $label)
                                @php($optionPricing = $feeType->pricing_options[$option] ?? [])
                                <section class="form-section mt-3">
                                    <div class="form-section__header"><span class="form-section__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h4>{{ $label }}</h4><p>Le client peut choisir l’une de ces deux modalités.</p></div></div>
                                    <div class="form-section__body"><div class="form-grid">
                                        <x-auth-input name="pricing_options[{{ $option }}][total]" label="Montant total (USD)" type="number" min="0.01" step="0.01" :value="old('pricing_options.'.$option.'.total', $optionPricing['total'] ?? '')" required />
                                        <x-auth-input name="pricing_options[{{ $option }}][monthly]" label="Montant mensuel (USD)" type="number" min="0.01" step="0.01" :value="old('pricing_options.'.$option.'.monthly', $optionPricing['monthly'] ?? '')" required />
                                        @error('pricing_options.'.$option.'.total')<span class="form-field__error">{{ $message }}</span>@enderror
                                        @error('pricing_options.'.$option.'.monthly')<span class="form-field__error">{{ $message }}</span>@enderror
                                    </div></div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-auth-input name="default_amount" label="Montant de référence (USD)" type="number" min="0.01" step="0.01" :value="old('default_amount', $feeType->default_amount)" />
                        @error('default_amount')<span class="form-field__error">{{ $message }}</span>@enderror
                    @endif
                    @error('name')<span class="form-field__error">{{ $message }}</span>@enderror
                </div></div>
            </section>
            <div class="form-actions"><a href="{{ route('ancillary-fee-types.index') }}" class="btn btn-outline">{{ __("Annuler") }}</a><button class="btn btn-primary" type="submit">{{ $feeType->exists ? 'Enregistrer les modifications' : 'Créer le type' }}</button></div>
        </form>
    </div>
</x-layouts.app>
