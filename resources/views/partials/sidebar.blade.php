@php
    $user = auth()->user();
    $profile = $user?->profile;
    $role = $user?->role ?? 'guest';
    $path = request()->path();
    $tab = request()->query('tab', '');
    $isAdmin = $role === 'admin';
    $isSupervisor = $role === 'supervisor';
    $can = fn (string $key) => \App\Models\RolePermission::allows($key, $user);
    $canAdminTools = $can('manage_atolls') || $can('manage_islands') || $can('view_users') || $can('manage_departments');
@endphp

<div class="flex h-full flex-col bg-[#1a5e5e] text-white dark:bg-[#0d2e2e]">
    {{-- Header --}}
    <div class="sidebar-header p-6 pb-2">
        <a href="{{ route('dashboard') }}" class="sidebar-brand group mb-6 flex cursor-pointer items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-white/10 text-white shadow-inner transition-all duration-300 group-hover:bg-white/20">
                <x-icon name="list-todo" class="h-6 w-6" />
            </div>
            <div class="sidebar-brand-copy flex flex-col">
                <h1 class="text-xl font-black uppercase leading-none tracking-tighter text-white">
                    RAHS<span class="text-teal-200">Portal</span>
                </h1>
                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-white/50">Official Task System</p>
            </div>
        </a>

        {{-- Global search --}}
        <form action="{{ route('tasks.index') }}" method="GET" class="sidebar-search relative mb-4">
            <x-icon name="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-white/30" />
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search portal..."
                class="h-9 w-full rounded-md border border-white/10 bg-white/5 pl-9 text-xs text-white transition-all placeholder:text-white/20 focus:bg-white/10"
            >
        </form>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-y-auto no-scrollbar px-3">
        {{-- Quick actions --}}
        @if ($can('create_tasks'))
        <div class="sidebar-quick-actions mb-4">
            <a
                href="{{ route('tasks.index', ['create' => 'true']) }}"
                class="group flex h-11 w-full items-center justify-start rounded-lg border border-teal-500/50 bg-teal-700 text-[10px] font-black uppercase tracking-wider text-white shadow-sm transition-all hover:bg-teal-600 active:scale-[0.98]"
            >
                <div class="sidebar-create-icon mr-3 rounded-md bg-white/20 p-1 transition-transform duration-300 group-hover:rotate-90">
                    <x-icon name="plus" class="h-3.5 w-3.5" />
                </div>
                <span class="sidebar-label">Create New Task</span>
            </a>
        </div>
        @endif

        <div class="sidebar-divider my-4 h-px bg-white/10"></div>

        {{-- System Navigation --}}
        <div class="sidebar-section-label mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">System Navigation</div>
        <nav class="space-y-1">
            @php
                $mainNav = [];
                if ($can('view_dashboard')) $mainNav[] = ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'path' => route('dashboard'), 'active' => $path === 'dashboard'];
                if ($can('view_tasks')) $mainNav[] = ['label' => 'Task Manager', 'icon' => 'list-todo', 'path' => route('tasks.index'), 'active' => $path === 'tasks'];
                if ($can('view_hospitals')) $mainNav[] = ['label' => 'Hospitals', 'icon' => 'building-2', 'path' => route('hospitals.index'), 'active' => $path === 'hospitals'];
                $mainNav[] = ['label' => 'Coordinators', 'icon' => 'users', 'path' => route('coordinators.index'), 'active' => $path === 'coordinators'];
                $mainNav[] = ['label' => 'Important Contacts', 'icon' => 'phone', 'path' => route('important-contacts.index'), 'active' => $path === 'important-contacts'];
                if ($can('view_reports')) {
                    $mainNav[] = ['label' => 'Reports', 'icon' => 'file-bar-chart', 'path' => route('reports.index'), 'active' => $path === 'reports'];
                }
                $mainNav[] = ['label' => 'Staff Leave', 'icon' => 'user-round-check', 'path' => route('leaves.index'), 'active' => $path === 'critical-staff-leave-management'];
                if ($can('view_operations')) $mainNav[] = ['label' => 'Operations', 'icon' => 'activity', 'path' => route('operations.index'), 'active' => $path === 'hospital-operations'];
                if ($isAdmin) {
                    $mainNav[] = ['label' => 'Contacts Admin', 'icon' => 'shield', 'path' => route('important-contacts.admin'), 'active' => $path === 'important-contacts-admin'];
                }
                $mainNav[] = ['label' => 'Settings', 'icon' => 'settings', 'path' => route('settings.index'), 'active' => $path === 'settings'];
            @endphp
            @foreach ($mainNav as $item)
                <a
                    href="{{ $item['path'] }}"
                    class="sidebar-nav-item group flex h-11 items-center rounded-lg px-3 transition-all duration-200 {{ $item['active'] ? 'bg-white font-black text-primary shadow-md' : 'text-white/70 hover:bg-white/5 hover:text-white' }}"
                >
                    <x-icon name="{{ $item['icon'] }}" class="{{ $item['active'] ? 'text-primary' : 'text-white/40 group-hover:text-white/70' }} mr-3 h-4 w-4 transition-colors" />
                    <span class="sidebar-label text-sm font-bold tracking-tight">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Access Control --}}
        @if ($canAdminTools || $isAdmin)
            <div class="sidebar-admin mt-6">
                <div class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">Access Control</div>
                <div x-data="{ open: true }" class="group/collapsible">
                    <button type="button" @click="open = !open" class="flex h-11 w-full items-center rounded-lg px-3 text-white/70 transition-all hover:bg-white/5 hover:text-white">
                        <x-icon name="shield" class="mr-3 h-4 w-4 text-white/40" />
                        <span class="text-sm font-bold tracking-tight">Admin Panel</span>
                        <span class="ml-auto transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <x-icon name="chevron-down" class="h-3.5 w-3.5" />
                        </span>
                    </button>
                    <div x-show="open">
                        <div class="ml-4 border-l border-white/10 py-1">
                            @php
                                $adminNav = [];
                                if ($can('manage_atolls')) $adminNav[] = ['label' => 'Atoll Records', 'icon' => 'map-pin', 'path' => route('dashboard', ['tab' => 'atolls']), 'active' => $path === 'dashboard' && $tab === 'atolls'];
                                if ($can('manage_islands')) $adminNav[] = ['label' => 'Island Records', 'icon' => 'building', 'path' => route('dashboard', ['tab' => 'islands']), 'active' => $path === 'dashboard' && $tab === 'islands'];
                                if ($can('view_users')) $adminNav[] = ['label' => 'User Database', 'icon' => 'users', 'path' => route('dashboard', ['tab' => 'users']), 'active' => $path === 'dashboard' && $tab === 'users'];
                                if ($can('manage_departments')) $adminNav[] = ['label' => 'Departments', 'icon' => 'building-2', 'path' => route('dashboard', ['tab' => 'user-departments']), 'active' => $path === 'dashboard' && $tab === 'user-departments'];
                                if ($can('manage_departments')) $adminNav[] = ['label' => 'Task Types', 'icon' => 'tag', 'path' => route('dashboard', ['tab' => 'departments']), 'active' => $path === 'dashboard' && $tab === 'departments'];
                                if ($isAdmin) {
                                    array_splice($adminNav, 5, 0, [[
                                        'label' => 'Role Permissions',
                                        'icon' => 'shield',
                                        'path' => route('role-permissions.index'),
                                        'active' => $path === 'role-permissions',
                                    ]]);
                                }
                            @endphp
                            @foreach ($adminNav as $item)
                                <a
                                    href="{{ $item['path'] }}"
                                    class="flex h-9 items-center px-3 text-xs transition-all {{ $item['active'] ? 'font-bold text-teal-300' : 'text-white/50 hover:text-white' }}"
                                >
                                    <x-icon name="{{ $item['icon'] }}" class="mr-2 h-3.5 w-3.5 opacity-50" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="mt-auto space-y-4 p-4">
        {{-- Sign out --}}
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="group flex h-11 w-full items-center justify-start rounded-lg border border-transparent text-white/50 transition-all duration-300 hover:border-rose-500/20 hover:bg-rose-500/10 hover:text-white">
                <div class="mr-3 rounded-md bg-rose-500/10 p-1.5 transition-colors group-hover:bg-rose-500/20">
                    <x-icon name="log-out" class="h-4 w-4 text-rose-400" />
                </div>
                <span class="sidebar-label text-sm font-bold tracking-tight text-white/70 group-hover:text-white">Sign Out</span>
                <x-icon name="chevron-right" class="ml-auto h-4 w-4 text-white/30 opacity-0 transition-all group-hover:opacity-40" />
            </button>
        </form>
    </div>
</div>
