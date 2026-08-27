<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-authenticated" content="true">
    <meta name="theme-color" content="#1a5e5e">
    <title>@yield('title', 'RAHS Portal') — RAHS Task System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%231a5e5e'/><text x='50' y='68' font-size='48' text-anchor='middle' font-family='sans-serif' font-weight='bold' fill='%2380CBC4'>R</text></svg>">
</head>
<body class="app-ui min-h-screen bg-background text-foreground antialiased" x-data x-init="$store.theme.init(); $store.notifications.init()">
<a href="#main-content" class="fixed left-4 top-4 z-[70] -translate-y-24 rounded-full bg-primary px-4 py-3 font-semibold text-primary-foreground shadow-lg transition-transform focus:translate-y-0">Skip to content</a>
<div x-show="$store.network.active" x-cloak class="fixed inset-x-0 top-0 z-[80] h-0.5 overflow-hidden bg-primary/15" role="progressbar" aria-label="Loading"><span class="block h-full w-1/3 animate-network-progress bg-primary"></span></div>
<div x-data="connectivityStatus" @online.window="check(true)" @offline.window="check()" x-show="!online" x-cloak class="offline-banner fixed inset-x-0 top-0 z-[90] flex min-h-11 items-center justify-center gap-2 px-4 py-2 text-center text-sm font-semibold" role="status" aria-live="assertive">
    <x-icon name="wifi-off" class="h-4 w-4 shrink-0" />
    <span>You’re offline. Existing information remains available; changes will require a connection.</span>
</div>
<div class="app-frame flex min-h-screen w-full bg-background">

    {{-- Desktop sidebar --}}
    <aside class="app-sidebar sticky top-0 hidden h-dvh shrink-0 p-3 transition-[width] duration-300 lg:flex" :class="$store.sidebar.collapsed ? 'sidebar-collapsed w-[5.25rem]' : 'w-[17.5rem]'">
        @include('partials.sidebar')
    </aside>

    {{-- Mobile sidebar drawer --}}
    <div x-show="$store.sidebar.open" x-cloak class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="$store.sidebar.open = false">
        <div class="absolute inset-0 bg-black/60" @click="$store.sidebar.open = false" aria-hidden="true"></div>
        <div class="absolute inset-y-0 left-0 w-72 max-w-[85vw] shadow-2xl animate-slide-in-from-left" role="dialog" aria-modal="true" aria-label="Portal navigation">
            @include('partials.sidebar')
        </div>
    </div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="desktop-utility-bar sticky top-0 z-30 hidden h-16 items-center gap-3 border-b border-border/70 bg-background/92 px-5 backdrop-blur-xl lg:flex">
            <button type="button" @click="$store.sidebar.toggleCollapsed()" class="touch-target inline-flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground" :aria-label="$store.sidebar.collapsed ? 'Expand navigation' : 'Collapse navigation'" :aria-expanded="!$store.sidebar.collapsed">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
            <div class="h-6 w-px bg-border"></div>
            <div x-data="globalSearch" class="relative w-full max-w-md" @click.outside="close()">
                <label class="relative block">
                    <span class="sr-only">Search the portal</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input type="search" x-model="query" @focus="query.length >= 2 && (open = true)" @keydown.escape="close()" class="gov-input h-10 min-h-10 bg-card pl-9 pr-10 text-sm" placeholder="Search tasks, hospitals, or users…">
                    <x-icon name="loader-2" x-show="searching" x-cloak class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-primary" />
                </label>
                <div x-show="open" x-cloak class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-border bg-card shadow-xl" role="region" aria-label="Search results">
                    <div class="max-h-96 overflow-y-auto p-2">
                        <template x-if="!searching && !results.tasks.length && !results.hospitals.length && !results.users.length"><p class="px-3 py-8 text-center text-sm text-muted-foreground">No matching records found.</p></template>
                        <template x-for="task in results.tasks" :key="`task-${task.id}`"><a href="{{ route('tasks.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-muted"><x-icon name="clipboard-list" class="h-4 w-4 text-primary" /><div class="min-w-0"><p class="truncate text-sm font-semibold" x-text="task.title || task.name"></p><p class="text-xs text-muted-foreground">Task</p></div></a></template>
                        <template x-for="hospital in results.hospitals" :key="`hospital-${hospital.id}`"><a href="{{ route('hospitals.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-muted"><x-icon name="building-2" class="h-4 w-4 text-primary" /><div class="min-w-0"><p class="truncate text-sm font-semibold" x-text="hospital.hospital_name || hospital.name"></p><p class="text-xs text-muted-foreground">Hospital</p></div></a></template>
                        <template x-for="user in results.users" :key="`user-${user.id}`"><a href="{{ route('dashboard') }}?tab=users" class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-muted"><x-icon name="user" class="h-4 w-4 text-primary" /><div class="min-w-0"><p class="truncate text-sm font-semibold" x-text="user.full_name || user.email"></p><p class="text-xs text-muted-foreground">User</p></div></a></template>
                    </div>
                </div>
            </div>
            <div class="ml-auto flex items-center gap-1.5">
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open; $store.notifications.refresh()" class="touch-target relative inline-flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground" aria-label="Notifications" :aria-expanded="open"><x-icon name="bell" class="h-4.5 w-4.5" /><span x-show="$store.notifications.unread > 0" x-cloak class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive ring-2 ring-background"></span></button>
                    <div x-show="open" x-cloak class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-xl border border-border bg-card shadow-xl">
                        <div class="flex items-center justify-between border-b border-border px-4 py-3"><p class="text-sm font-semibold">Notifications</p><button type="button" @click="$store.notifications.markAllRead()" class="text-xs font-medium text-primary hover:underline">Mark all read</button></div>
                        <div class="max-h-80 overflow-y-auto"><template x-if="$store.notifications.items.length === 0"><p class="px-4 py-8 text-center text-sm text-muted-foreground">You’re all caught up.</p></template><template x-for="n in $store.notifications.items" :key="n.id"><button type="button" @click="$store.notifications.markRead(n)" class="flex w-full gap-3 border-b border-border/60 px-4 py-3 text-left last:border-0 hover:bg-muted/60"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="n.is_read ? 'bg-muted-foreground/30' : 'bg-primary'"></span><span class="min-w-0"><span class="block truncate text-sm font-medium" x-text="n.title"></span><span class="mt-0.5 block line-clamp-2 text-xs text-muted-foreground" x-text="n.message"></span></span></button></template></div>
                    </div>
                </div>
                <button type="button" @click="$store.theme.toggle()" class="touch-target inline-flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground" aria-label="Toggle color theme"><x-icon name="moon" x-show="!$store.theme.dark" class="h-4.5 w-4.5" /><x-icon name="sun" x-show="$store.theme.dark" class="h-4.5 w-4.5" /></button>
                <a href="{{ route('settings.index') }}" class="ml-1 flex items-center gap-2 rounded-lg border border-border bg-card px-2 py-1.5 hover:bg-secondary" aria-label="Open account settings"><x-profile-avatar :profile="auth()->user()?->profile" size="sm" /><span class="hidden max-w-32 truncate text-sm font-semibold xl:block">{{ auth()->user()?->profile?->full_name ?? auth()->user()?->email }}</span></a>
            </div>
        </header>
        <header class="mobile-header sticky top-0 z-30 px-4 pb-3 pt-[calc(env(safe-area-inset-top,0px)+0.75rem)] lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center justify-between">
                <button type="button" @click="$store.sidebar.open = true" class="touch-target -ml-2 inline-flex items-center justify-center rounded-full hover:bg-secondary" aria-label="Open navigation" :aria-expanded="$store.sidebar.open"><x-icon name="menu" class="h-5 w-5" /></button>
                <a href="{{ route('dashboard') }}" class="font-black tracking-tight">RAHS<span class="text-primary">Portal</span></a>
                <button type="button" @click="$store.theme.toggle()" class="touch-target -mr-2 inline-flex items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-foreground" aria-label="Toggle color theme"><x-icon name="moon" x-show="!$store.theme.dark" class="h-5 w-5" /><x-icon name="sun" x-show="$store.theme.dark" class="h-5 w-5" /></button>
            </div>
        </header>
        <main id="main-content" tabindex="-1" class="no-scrollbar flex-1 overflow-y-auto overscroll-contain pb-[92px] pt-5 md:pb-5 lg:pt-8">
            <div class="page-shell page-enter">
                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- Bottom navigation (mobile) --}}
<nav class="fixed bottom-0 left-0 right-0 z-40 md:hidden safe-bottom" aria-label="Primary navigation">
    <div class="relative mx-3 mb-3">
        <div class="absolute inset-0 rounded-2xl border border-border/70 bg-background/95 shadow-[0_16px_50px_-20px_rgba(0,0,0,.35)] backdrop-blur-xl"></div>
        <div class="relative flex h-[68px] items-center justify-around px-1">
            @php
                $path = request()->path();
                $bottomTabs = [
                    ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'path' => route('dashboard'), 'active' => $path === 'dashboard'],
                    ['label' => 'Tasks', 'icon' => 'clipboard-list', 'path' => route('tasks.index'), 'active' => $path === 'tasks'],
                    ['label' => 'Coordinators', 'icon' => 'users', 'path' => route('coordinators.index'), 'active' => $path === 'coordinators'],
                    ['label' => 'Hospitals', 'icon' => 'building-2', 'path' => route('hospitals.index'), 'active' => $path === 'hospitals'],
                ];
            @endphp
            <div class="flex flex-1 items-center justify-around">
                @foreach (array_slice($bottomTabs, 0, 2) as $tab)
                    <a href="{{ $tab['path'] }}" class="group relative flex h-full min-w-[56px] flex-col items-center justify-center gap-0.5 px-2 transition-all" aria-label="{{ $tab['label'] }}">
                        <div class="relative flex w-full items-center justify-center py-1">
                            @if ($tab['active'])
                                <div class="absolute -top-1 left-1/2 h-1 w-6 -translate-x-1/2 rounded-full bg-primary shadow-lg shadow-primary/50"></div>
                            @endif
                            <x-icon name="{{ $tab['icon'] }}" class="{{ $tab['active'] ? 'scale-110 text-primary' : 'text-muted-foreground/50 group-hover:text-muted-foreground/80' }} h-5 w-5 transition-all duration-300" />
                        </div>
                        <span class="{{ $tab['active'] ? 'text-primary' : 'text-muted-foreground' }} text-[10px] font-bold transition-all duration-300">{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Create button --}}
            <div class="relative -top-4 shrink-0">
                <div class="absolute -inset-2 rounded-full bg-primary/20 blur-xl"></div>
                <a href="{{ route('tasks.index', ['create' => 'true']) }}" class="group/btn flex h-14 w-14 items-center justify-center rounded-full border-[3px] border-background bg-primary text-primary-foreground shadow-xl shadow-primary/40 transition-all duration-300 hover:scale-110 active:scale-90" aria-label="Create task">
                    <x-icon name="plus" class="h-7 w-7 transition-transform duration-500 group-hover/btn:rotate-90" />
                </a>
            </div>

            <div class="flex flex-1 items-center justify-around">
                @foreach (array_slice($bottomTabs, 2) as $tab)
                    <a href="{{ $tab['path'] }}" class="group relative flex h-full min-w-[56px] flex-col items-center justify-center gap-0.5 px-2 transition-all" aria-label="{{ $tab['label'] }}">
                        <div class="relative flex w-full items-center justify-center py-1">
                            @if ($tab['active'])
                                <div class="absolute -top-1 left-1/2 h-1 w-6 -translate-x-1/2 rounded-full bg-primary shadow-lg shadow-primary/50"></div>
                            @endif
                            <x-icon name="{{ $tab['icon'] }}" class="{{ $tab['active'] ? 'scale-110 text-primary' : 'text-muted-foreground/50 group-hover:text-muted-foreground/80' }} h-5 w-5 transition-all duration-300" />
                        </div>
                        <span class="{{ $tab['active'] ? 'text-primary' : 'text-muted-foreground' }} text-[10px] font-bold transition-all duration-300">{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

{{-- Toasts --}}
<div class="toast-region fixed bottom-20 right-4 z-[60] space-y-2 lg:bottom-6" x-cloak aria-live="polite" aria-atomic="true">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div class="flex w-full max-w-sm items-start gap-3 rounded-lg border bg-card px-4 py-3 shadow-lg animate-slide-in-from-bottom"
             :class="t.type === 'error' ? '!border-destructive/50' : t.type === 'info' ? '!border-blue-500/50' : '!border-primary/50'">
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-destructive' : t.type === 'info' ? 'bg-blue-500' : 'bg-primary'"></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold" x-show="t.title" x-text="t.title"></p>
                <p class="text-sm" x-text="t.message"></p>
                <button type="button" x-show="t.action" @click="t.action.callback(); $store.toast.dismiss(t.id)" class="mt-1.5 text-xs font-bold text-primary hover:underline" x-text="t.action?.label"></button>
            </div>
            <button type="button" @click="$store.toast.dismiss(t.id)" class="touch-target -m-2 shrink-0 text-muted-foreground hover:text-foreground" aria-label="Dismiss notification">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>

{{-- Shared structured input dialog --}}
<div x-show="$store.prompt.open" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" @keydown.escape.window="$store.prompt.open && $store.prompt.finish(null)" role="dialog" aria-modal="true" aria-labelledby="global-prompt-title" aria-describedby="global-prompt-description">
    <button type="button" class="absolute inset-0 cursor-default" @click="$store.prompt.finish(null)" aria-label="Close dialog"></button>
    <form @submit.prevent="$store.prompt.submit()" class="relative max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl border border-border bg-card shadow-2xl sm:max-w-lg sm:rounded-xl">
        <div class="border-b border-border px-5 py-4 sm:px-6"><h2 id="global-prompt-title" class="text-lg font-bold" x-text="$store.prompt.title"></h2><p id="global-prompt-description" x-show="$store.prompt.description" class="mt-1 text-sm leading-6 text-muted-foreground" x-text="$store.prompt.description"></p></div>
        <div class="space-y-4 p-5 sm:p-6">
            <template x-for="(field, index) in $store.prompt.fields" :key="field.name">
                <label class="block" :for="`global-prompt-${field.name}`"><span class="mb-1.5 block text-sm font-medium"><span x-text="field.label"></span><span x-show="field.required" class="text-destructive"> *</span></span>
                    <template x-if="field.type === 'textarea'"><textarea :id="`global-prompt-${field.name}`" x-model="$store.prompt.values[field.name]" :rows="field.rows || 5" :placeholder="field.placeholder || ''" :required="field.required" class="gov-input resize-y"></textarea></template>
                    <template x-if="field.type !== 'textarea'"><input :id="`global-prompt-${field.name}`" :type="field.type || 'text'" x-model="$store.prompt.values[field.name]" :placeholder="field.placeholder || ''" :required="field.required" class="gov-input"></template>
                    <span x-show="field.help" class="mt-1.5 block text-xs text-muted-foreground" x-text="field.help"></span>
                </label>
            </template>
        </div>
        <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-border bg-card px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><button type="button" @click="$store.prompt.finish(null)" class="gov-btn gov-btn-outline">Cancel</button><button type="submit" class="gov-btn gov-btn-primary" x-text="$store.prompt.submitLabel"></button></div>
    </form>
</div>

{{-- Shared confirmation dialog --}}
<div x-show="$store.confirm.open" x-cloak x-effect="$store.confirm.open && $nextTick(() => $refs.globalConfirmButton.focus())" class="fixed inset-0 z-[70] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" @keydown.escape.window="$store.confirm.open && $store.confirm.finish(false)" role="alertdialog" aria-modal="true" aria-labelledby="global-confirm-title" aria-describedby="global-confirm-message">
    <button type="button" class="absolute inset-0 cursor-default" @click="$store.confirm.finish(false)" aria-label="Cancel confirmation"></button>
    <div class="relative w-full rounded-t-2xl border border-border bg-card p-5 shadow-2xl sm:max-w-md sm:rounded-xl sm:p-6">
        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="$store.confirm.tone === 'danger' ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'"><x-icon name="alert-triangle" class="h-5 w-5" /></span>
            <div class="min-w-0 flex-1"><h2 id="global-confirm-title" class="text-lg font-bold" x-text="$store.confirm.title || 'Confirm action'"></h2><p id="global-confirm-message" class="mt-1.5 text-sm leading-6 text-muted-foreground" x-text="$store.confirm.message || 'Are you sure you want to continue?'"></p></div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" @click="$store.confirm.finish(false)" class="gov-btn gov-btn-outline">Cancel</button>
            <button type="button" @click="$store.confirm.finish(true)" class="gov-btn" :class="$store.confirm.tone === 'danger' ? 'gov-btn-danger' : 'gov-btn-primary'" x-text="$store.confirm.confirmLabel || 'Confirm'" x-ref="globalConfirmButton"></button>
        </div>
    </div>
</div>

@yield('scripts')
@include('partials.sw-cleanup')
</body>
</html>
