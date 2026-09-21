@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])
<label class="form-field">
    <span class="form-field__label">{{ $label }}</span>
    <input name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" autocomplete="{{ $autocomplete }}"
        {{ $attributes->class(['form-control']) }}>
    @error($name)<span class="form-field__error">{{ $message }}</span>@enderror
</label>
