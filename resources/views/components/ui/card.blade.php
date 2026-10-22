@props(['title' => null, 'description' => null, 'flush' => false])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface shadow-[0_1px_2px_0_rgb(15_23_42/0.03)]']) }}>
    @if ($title)
        <header class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-xs text-muted">{{ $description }}</p>
            @endif
        </header>
    @endif

    <div @class(['p-5' => ! $flush])>
        {{ $slot }}
    </div>
</section>
