@props(['user' => null, 'roles'])

<div class="form-grid">
    <x-auth-input name="first_name" label="Prénom" :value="old('first_name', $user?->first_name)" required />
    <x-auth-input name="last_name" label="Nom" :value="old('last_name', $user?->last_name)" required />
    <x-auth-input name="email" label="Adresse e-mail" type="email" :value="old('email', $user?->email)" required autocomplete="off" />
    <x-auth-input name="phone" label="Téléphone" :value="old('phone', $user?->phone)" required />

    <label class="form-field">
        <span class="form-field__label">Rôle<span class="text-danger ms-1 fw-bold">*</span></span>
        <select name="role_id" class="form-select" required>
            @foreach($roles as $role)
                @if($role->name === 'super_admin' && ! auth()->user()?->hasRole('super_admin'))
                    @continue
                @endif
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->roles->where('name', '!=', 'customer')->first()?->id) === (string) $role->id)>
                    {{ ucfirst(str_replace('_', ' ', $role->name)) }} · {{ $role->description }}
                </option>
            @endforeach
        </select>
        @error('role_id')<span class="form-field__error">{{ $message }}</span>@enderror
    </label>

    @if($user)
        <label class="form-field">
            <span class="form-field__label">Statut<span class="text-danger ms-1 fw-bold">*</span></span>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $user->status) === 'active')>Actif</option>
                <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspendu</option>
            </select>
            @error('status')<span class="form-field__error">{{ $message }}</span>@enderror
        </label>

        <div class="form-subsection form-grid__wide">
            <div class="form-subsection__header"><h3>Réinitialisation du mot de passe</h3><p>Laissez ces champs vides pour conserver le mot de passe actuel.</p></div>
            <div class="form-grid">
                <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" />
                <x-auth-input name="password_confirmation" label="Confirmation" type="password" autocomplete="new-password" />
            </div>
        </div>
    @endif
</div>
