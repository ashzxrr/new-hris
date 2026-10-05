<div class="form-group">
    @if($label ?? null)
        <label for="{{ $name ?? null }}">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if($errors->has($name ?? null))
        <div class="form-error">
            {{ $errors->first($name) }}
        </div>
    @endif
</div>
