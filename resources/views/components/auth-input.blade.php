@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false])
<label class="form-field">
    <span class="form-field__label">{{ $label }}@if($required || $attributes->has('required'))<span class="text-danger text-red-500 ms-1 font-bold" style="color: var(--app-danger, #dc3545);">*</span>@endif</span>
    <input name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" autocomplete="{{ $autocomplete }}"
        {{ $attributes->class(['form-control']) }}>
    @error($name)<span class="form-field__error">{{ $message }}</span>@enderror
</label>

