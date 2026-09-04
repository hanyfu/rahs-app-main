@props(['title', 'description' => null, 'eyebrow' => null, 'icon' => null])

<header {{ $attributes->merge(['class' => 'page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0 max-w-3xl">
        @if ($eyebrow)
            <p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-primary">
                @if ($icon)<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10"><x-icon :name="$icon" class="h-3.5 w-3.5" /></span>@endif
                {{ $eyebrow }}
            </p>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ $title }}</h1>
        @if ($description)<div class="mt-1.5 text-sm leading-6 text-muted-foreground">{{ $description }}</div>@endif
    </div>
    @isset($actions)<div class="page-header-actions flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto">{{ $actions }}</div>@endisset
</header>
