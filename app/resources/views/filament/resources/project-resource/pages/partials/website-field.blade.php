@php
    $inputId = str_replace(['.', '_'], '-', $path);
@endphp

<div class="wm-website-field {{ ($full ?? false) ? 'is-full' : '' }}">
    <label for="{{ $inputId }}">{{ $label }}</label>
    @if ($textarea ?? false)
        <textarea id="{{ $inputId }}" class="wm-website-textarea" wire:model="{{ $path }}"></textarea>
    @else
        <input id="{{ $inputId }}" class="wm-website-input" type="text" wire:model="{{ $path }}">
    @endif
</div>
