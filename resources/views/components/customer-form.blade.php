@props(['customer' => null, 'agents'])
<div class="form-grid" data-customer-form>
    <div class="form-field form-grid__wide mb-3">
        <label class="form-field__label">Photo de profil / Avatar</label>
        <div class="d-flex align-items-center gap-3">
            @if($customer?->avatar_path)
                <img src="{{ Storage::url($customer->avatar_path) }}" alt="{{ $customer->first_name }}" class="rounded-circle object-cover" style="width: 54px; height: 54px;">
            @endif
            <div class="flex-grow-1">
                <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp" class="form-control @error('avatar') is-invalid @enderror">
                @error('avatar')<span class="form-field__error">{{ $message }}</span>@enderror
            </div>
            @if($customer?->avatar_path)
                <label class="form-check-label text-danger small cursor-pointer">
                    <input type="checkbox" name="remove_avatar" value="1" class="form-check-input me-1"> Supprimer
                </label>
            @endif
        </div>
    </div>
    @foreach ([['first_name','Prénom'],['last_name','Nom'],['middle_name','Postnom'],['phone','Téléphone principal'],['secondary_phone','Téléphone secondaire'],['whatsapp','WhatsApp'],['email','E-mail'],['commune','Commune'],['city','Ville'],['country','Pays'],['nationality','Nationalité']] as [$name,$label])
        <x-auth-input :name="$name" :label="$label" :type="$name === 'email' ? 'email' : 'text'" :value="old($name, $customer?->{$name})" :required="in_array($name, ['first_name','last_name','phone'])" />
    @endforeach
    <x-auth-input name="birth_date" label="Date de naissance" type="date" :value="old('birth_date', $customer?->birth_date?->format('Y-m-d'))" />
    <label class="form-field"><span class="form-field__label">Statut <span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span></span><select name="status" class="form-select">@foreach(['prospect'=>'Prospect','active'=>'Actif','settled'=>'Soldé','suspended'=>'Suspendu'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$customer?->status ?? 'active')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label class="form-field"><span class="form-field__label">Commercial responsable</span><select name="assigned_to" class="form-select"><option value="">Non attribué</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string)old('assigned_to',$customer?->assigned_to)===(string)$agent->id)>{{ $agent->first_name }} {{ $agent->last_name }}</option>@endforeach</select></label>
    <label class="form-field form-grid__wide"><span class="form-field__label">Adresse</span><textarea name="address" id="address" rows="2" class="form-control" placeholder="Entrez une adresse (ex: Rue des Jardins, Abidjan...)">{{ old('address',$customer?->address) }}</textarea></label>
    <label class="form-field form-grid__wide"><span class="form-field__label">Observations internes</span><textarea name="internal_notes" rows="4" class="form-control">{{ old('internal_notes',$customer?->internal_notes) }}</textarea></label>
</div>

