@props(['label', 'name' => null, 'required' => false, 'hint' => null])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <label @if ($name) for="{{ $name }}" @endif class="block text-sm font-medium text-ink">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($name && $errors->has($name))
        <p class="text-xs text-danger">{{ $errors->first($name) }}</p>
    @endif
</div>
