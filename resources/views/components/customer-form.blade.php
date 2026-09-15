@props(['customer' => null, 'agents'])
<div class="grid gap-5 md:grid-cols-2">
    @foreach ([['first_name','Prénom'],['last_name','Nom'],['middle_name','Postnom'],['phone','Téléphone principal'],['secondary_phone','Téléphone secondaire'],['whatsapp','WhatsApp'],['email','E-mail'],['commune','Commune'],['city','Ville'],['country','Pays'],['nationality','Nationalité']] as [$name,$label])
        <x-auth-input :name="$name" :label="$label" :type="$name === 'email' ? 'email' : 'text'" :value="old($name, $customer?->{$name})" :required="in_array($name, ['first_name','last_name','phone'])" />
    @endforeach
    <x-auth-input name="birth_date" label="Date de naissance" type="date" :value="old('birth_date', $customer?->birth_date?->format('Y-m-d'))" />
    <label class="flex flex-col gap-2 text-sm font-medium">Statut<select name="status" class="rounded-lg border border-slate-300 px-3 py-2.5">@foreach(['prospect'=>'Prospect','active'=>'Actif','settled'=>'Soldé','suspended'=>'Suspendu'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$customer?->status ?? 'active')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label class="flex flex-col gap-2 text-sm font-medium">Commercial responsable<select name="assigned_to" class="rounded-lg border border-slate-300 px-3 py-2.5"><option value="">Non attribué</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string)old('assigned_to',$customer?->assigned_to)===(string)$agent->id)>{{ $agent->first_name }} {{ $agent->last_name }}</option>@endforeach</select></label>
    <label class="flex flex-col gap-2 text-sm font-medium md:col-span-2">Adresse<textarea name="address" rows="2" class="rounded-lg border border-slate-300 px-3 py-2.5">{{ old('address',$customer?->address) }}</textarea></label>
    <label class="flex flex-col gap-2 text-sm font-medium md:col-span-2">Observations internes<textarea name="internal_notes" rows="4" class="rounded-lg border border-slate-300 px-3 py-2.5">{{ old('internal_notes',$customer?->internal_notes) }}</textarea></label>
</div>
