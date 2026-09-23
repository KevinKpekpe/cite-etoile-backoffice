@props(['user' => null, 'roles'])
<div class="grid gap-5 md:grid-cols-2">
    <x-auth-input name="first_name" label="Prénom" :value="old('first_name', $user?->first_name)" required />
    <x-auth-input name="last_name" label="Nom" :value="old('last_name', $user?->last_name)" required />
    <x-auth-input name="email" label="E-mail" type="email" :value="old('email', $user?->email)" required autocomplete="off" />
    <x-auth-input name="phone" label="Téléphone" :value="old('phone', $user?->phone)" required />

    <label class="flex flex-col gap-2 text-sm font-medium"><span>Rôle <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span></span>
        <select name="role_id" class="rounded-lg border border-slate-300 px-3 py-2.5">
            @foreach($roles as $role)
                @if($role->name === 'super_admin' && ! auth()->user()?->hasRole('super_admin'))
                    @continue
                @endif
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->roles->where('name', '!=', 'customer')->first()?->id) === (string) $role->id)>
                    {{ ucfirst(str_replace('_', ' ', $role->name)) }} — {{ $role->description }}
                </option>
            @endforeach
        </select>
        @error('role_id')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
    </label>

    @if($user)
    <label class="flex flex-col gap-2 text-sm font-medium"><span>Statut <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span></span>
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2.5">
            <option value="active" @selected(old('status', $user->status) === 'active')>Actif</option>
            <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspendu</option>
        </select>
        @error('status')<span class="text-xs text-red-700">{{ $message }}</span>@enderror
    </label>
    @endif

    @if($user)
    <div class="md:col-span-2 border-t pt-4">
        <p class="mb-3 text-sm font-semibold text-slate-700">Réinitialiser le mot de passe <span class="font-normal text-slate-500">(laisser vide pour ne pas modifier)</span></p>
        <div class="grid gap-5 md:grid-cols-2">
            <x-auth-input name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" />
            <x-auth-input name="password_confirmation" label="Confirmer" type="password" autocomplete="new-password" />
        </div>
    </div>
    @endif
</div>
