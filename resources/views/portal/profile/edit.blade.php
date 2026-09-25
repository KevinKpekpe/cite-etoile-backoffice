<x-layouts.app title="Mon profil">
    <div class="form-page portal-page">
        <header class="resource-heading">
            <div>
                <p class="app-kicker">Espace client</p>
                <h1 class="resource-heading__title">Mes informations personnelles</h1>
                <p class="resource-heading__description">Tenez vos coordonnées à jour pour le suivi de votre dossier.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="form-page__content">
            @csrf
            @method('PUT')
            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">01</span><div><h2>Photo de profil</h2><p>Ajoutez une photo pour votre compte client.</p></div></div>
                <div class="form-section__body">
                    <div class="d-flex align-items-center gap-4">
                        <div class="profile-avatar-preview">
                            @if($customer->avatar_path)
                                <img src="{{ Storage::url($customer->avatar_path) }}" alt="{{ $customer->first_name }}" class="rounded-circle object-cover" style="width: 80px; height: 80px;">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 80px; height: 80px;">
                                    {{ mb_strtoupper(mb_substr($customer->first_name, 0, 1).mb_substr($customer->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="form-label mb-1">Choisir une nouvelle photo</label>
                            <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp" class="form-control mb-2 @error('avatar') is-invalid @enderror">
                            @error('avatar')<span class="form-field__error d-block mb-2">{{ $message }}</span>@enderror
                            @if($customer->avatar_path)
                                <label class="form-check-label text-danger small cursor-pointer">
                                    <input type="checkbox" name="remove_avatar" value="1" class="form-check-input me-1"> Supprimer la photo actuelle
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section__header"><span class="form-section__number">02</span><div><h2>Identité et contact</h2><p>Coordonnées utilisées pour vos contrats et communications.</p></div></div>
                <div class="form-section__body"><div class="form-grid form-grid--three" data-customer-form>
                    @foreach(['first_name' => 'Prénom', 'last_name' => 'Nom', 'middle_name' => 'Postnom', 'phone' => 'Téléphone principal', 'secondary_phone' => 'Téléphone secondaire', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'commune' => 'Commune', 'city' => 'Ville', 'country' => 'Pays', 'nationality' => 'Nationalité'] as $field => $label)
                        <label class="form-field">
                            <span class="form-field__label">{{ $label }}@if(in_array($field, ['first_name', 'last_name', 'phone']))<span class="form-required">*</span>@endif</span>
                            <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $customer->{$field}) }}" class="form-control @error($field) is-invalid @enderror" @required(in_array($field, ['first_name', 'last_name', 'phone']))>
                            @error($field)<span class="form-field__error">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                    <label class="form-field form-grid__wide"><span class="form-field__label">Adresse</span><textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>@error('address')<span class="form-field__error">{{ $message }}</span>@enderror</label>
                </div></div>
            </section>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer les modifications</button></div>
        </form>

        <section class="form-section profile-security-section">
            <div class="form-section__header"><span class="form-section__number">02</span><div><h2>Mot de passe</h2><p>Choisissez un mot de passe unique pour protéger votre espace.</p></div></div>
            <div class="form-section__body">
                <form method="POST" action="{{ route('portal.profile.password.update') }}" class="form-page__content">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <x-auth-input name="current_password" label="Mot de passe actuel" type="password" autocomplete="current-password" required />
                        <p class="form-grid__wide profile-password-note">La modification du mot de passe ne change pas vos autres informations de profil.</p>
                        <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required />
                        <x-auth-input name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" required />
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">Mettre à jour le mot de passe</button></div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
