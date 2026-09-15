@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])
<label class="flex flex-col gap-2 text-sm font-medium text-slate-700">
    {{ $label }}
    <input name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" autocomplete="{{ $autocomplete }}"
        {{ $attributes->class(['rounded-lg border border-slate-300 px-3 py-2.5 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200']) }}>
    @error($name)<span class="text-xs text-red-700">{{ $message }}</span>@enderror
</label>
