@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null, 'required' => false])
<div class="form-group">
    <label class="form-label" for="{{ $name }}">
        {{ $label }}
        @if($required || $attributes->has('required'))<span class="form-required">*</span>@endif
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >
    @error($name)<span class="form-error">{{ $message }}</span>@enderror
</div>
