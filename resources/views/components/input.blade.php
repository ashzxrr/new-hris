@php
    $inputClasses = 'w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] 
                     rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 
                     focus:border-[#567C8D] placeholder-[#8BAFC4] transition';
@endphp

<div class="form-group">
    @if($label ?? null)
        <label for="{{ $name ?? null }}">{{ $label }}</label>
    @endif

    @if($type === 'textarea')
        <textarea 
            name="{{ $name }}" 
            id="{{ $name }}" 
            class="{{ $inputClasses }}"
            @if($rows ?? null) rows="{{ $rows }}" @endif
            placeholder="{{ $placeholder ?? '' }}"
            {{ $required ?? false ? 'required' : '' }}>{{ old($name, $value ?? '') }}</textarea>
    @elseif($type === 'select')
        <select 
            name="{{ $name }}" 
            id="{{ $name }}" 
            class="{{ $inputClasses }}"
            {{ $required ?? false ? 'required' : '' }}>
            <option value="">{{ $placeholder ?? 'Select...' }}</option>
            @foreach($options ?? [] as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ old($name, $value ?? '') == $optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
    @else
        <input 
            type="{{ $type ?? 'text' }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            class="{{ $inputClasses }}"
            placeholder="{{ $placeholder ?? '' }}"
            value="{{ old($name, $value ?? '') }}"
            {{ $required ?? false ? 'required' : '' }} />
    @endif

    @if($errors->has($name ?? null))
        <div class="form-error">
            {{ $errors->first($name) }}
        </div>
    @endif
</div>
