@props([
    'title' => '',
    'titleAccent' => '',
    'subtitle' => '',
    'onRefresh' => null,
    'onExport' => null,
    'exportUrl' => null,
    'exportLabel' => 'Export Data',
])

<header {{ $attributes->merge(['class' => 'mb-4 lg:mb-8 flex w-full items-center justify-between border-b border-border/50 bg-transparent px-4 py-3 lg:px-8 lg:py-4']) }}>
    <div class="flex items-center gap-3">
        <div class="md:hidden">
            <button type="button" @click="$store.sidebar.open = true" class="flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-all hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Open sidebar">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
        </div>
        <div class="flex flex-col">
            <h2 class="text-lg font-black uppercase leading-none tracking-tight text-slate-900 dark:text-white md:text-xl lg:text-3xl">
                {{ $title }}@if ($titleAccent)<span class="ml-0.5 text-primary">{{ $titleAccent }}</span>@endif
            </h2>
            <p class="ml-0.5 mt-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 opacity-60">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        @if ($exportUrl)
            <a
                href="{{ $exportUrl }}"
                class="hidden h-9 items-center gap-2 rounded-lg px-3 font-bold uppercase tracking-widest text-slate-500 transition-all hover:bg-slate-100 dark:hover:bg-slate-800 md:flex md:h-10 md:px-4 text-[10px]"
            >
                <x-icon name="download" class="h-3.5 w-3.5" />
                <span class="hidden lg:inline">{{ $exportLabel }}</span>
            </a>
        @elseif ($onExport)
            <button type="button" @click="{{ $onExport }}" class="hidden h-9 items-center gap-2 rounded-lg px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-all hover:bg-slate-100 dark:hover:bg-slate-800 md:flex md:h-10 md:px-4">
                <x-icon name="download" class="h-3.5 w-3.5" />
                <span class="hidden lg:inline">{{ $exportLabel }}</span>
            </button>
        @endif

        @if ($onRefresh)
            <button type="button" @click="{{ $onRefresh }}" class="flex h-9 w-9 items-center justify-center rounded-lg transition-all hover:bg-slate-100 dark:hover:bg-slate-800 md:h-10 md:w-10" aria-label="Refresh">
                <x-icon name="refresh-cw" class="h-3.5 w-3.5 text-slate-500" />
            </button>
        @endif
    </div>
</header>
