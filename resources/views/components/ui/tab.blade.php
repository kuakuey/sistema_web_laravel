@props(['href', 'active' => false, 'icon' => null])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    @class([
        '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
        'border-accent text-accent' => $active,
        'border-transparent text-muted hover:border-line hover:text-ink' => ! $active,
    ])
>
    @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</a>
