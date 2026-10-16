@props(['label', 'name', 'required' => false, 'hint' => null])
@php($hasError = $errors->has($name))
<div {{ $attributes->class('field-group '.($hasError ? 'field-invalid' : '')) }}>
    <label class="brutal-label" for="{{ $name }}">
        {{ $label }} @if($required)<span aria-hidden="true" class="text-danger">*</span>@endif
    </label>
    {{ $slot }}
    @if($hint)<p class="mt-1 text-xs text-ink/70">{{ $hint }}</p>@endif
    @error($name)<p class="form-error" role="alert">{{ $message }}</p>@enderror
</div>
