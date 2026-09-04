@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="relative z-0 min-h-screen overflow-x-hidden bg-background" x-data="{ tab: @js($tab), setTab(value) { this.tab = value; const url = new URL(window.location.href); url.searchParams.set('tab', value); history.replaceState(null, '', url); } }">
    <div class="mesh-gradient-bg pointer-events-none fixed inset-0 -z-10 opacity-40 dark:opacity-20"></div>

    <x-dashboard-header
        title="RAHS"
        titleAccent="DASHBOARD"
        subtitle="System Overview"
        onRefresh="window.location.reload()"
    />

    <main class="container mx-auto space-y-6 px-3 py-4 animate-fade-in sm:space-y-12 sm:px-4 sm:py-8">
        {{-- Welcome --}}
        <div class="space-y-4 sm:space-y-6">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-black tracking-tight sm:text-2xl md:text-3xl">
                        Welcome back{{ $profile?->full_name ? ', ' . $profile->full_name : ($profile?->first_name ? ', ' . $profile->first_name : '') }}
                    </h1>
                    <p class="mt-1 text-sm font-medium text-muted-foreground sm:text-base">Here's what's happening across the system</p>
                </div>
            </div>

            {{-- TaskStatistics --}}
            <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @php
                    $statCards = [
                        ['title' => 'Total Tasks', 'value' => $stats['total'], 'icon' => 'activity', 'active' => false, 'clickable' => false],
                        ['title' => 'Pending', 'value' => $stats['pending'], 'icon' => 'hourglass', 'active' => false, 'clickable' => false],
                        ['title' => 'In Progress', 'value' => $stats['inProgress'], 'icon' => 'zap', 'active' => false, 'clickable' => false],
                        ['title' => 'Completed', 'value' => $stats['completed'], 'icon' => 'badge-check', 'active' => false, 'clickable' => false],
                        ['title' => 'Overdue', 'value' => $stats['overdue'], 'icon' => 'flame', 'active' => false, 'clickable' => false],
                        ['title' => 'Efficiency', 'value' => $stats['efficiency'] . '%', 'icon' => 'target', 'active' => false, 'clickable' => false],
                    ];
                @endphp
                @foreach ($statCards as $stat)
                    <div class="group relative cursor-default overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-300">
                        <div class="flex h-full flex-col justify-between p-5">
                            <div class="flex items-start justify-between">
                                <div class="rounded-lg bg-slate-100 p-2.5 text-slate-600 transition-colors duration-300 group-hover:bg-primary/10 group-hover:text-primary dark:bg-slate-800 dark:text-slate-400">
                                    <x-icon name="{{ $stat['icon'] }}" class="h-5 w-5" />
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $stat['value'] }}</span>
                                </div>
                                <p class="mt-1 text-[10px] font-bold uppercase leading-none tracking-[0.15em] text-slate-500 dark:text-slate-400">{{ $stat['title'] }}</p>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 h-1 w-0 bg-primary/30 transition-all duration-300 group-hover:w-1/2"></div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Admin management tabs --}}
        @if ($permissionAccess->only(['manage_atolls','manage_islands','view_users','manage_departments'])->contains(true))
            <div class="w-full space-y-10">
                <nav aria-label="Administration sections" class="operations-nav-shell admin-command-nav">
                  <div class="operations-nav-core">
                    <button type="button" @click="setTab('overview')" :aria-current="tab==='overview'?'page':null" class="operations-nav-item" :class="tab==='overview'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="list-todo" class="h-4 w-4" /></span><span><strong>Overview</strong><small>System metrics</small></span>
                    </button>
                    @if($permissionAccess['manage_atolls'] ?? false)<button type="button" @click="setTab('atolls')" :aria-current="tab==='atolls'?'page':null" class="operations-nav-item" :class="tab==='atolls'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="map-pin" class="h-4 w-4" /></span><span><strong>Atolls</strong><small>Regional structure</small></span>
                    </button>@endif
                    <button type="button" @click="setTab('coordinators')" :aria-current="tab==='coordinators'?'page':null" class="operations-nav-item" :class="tab==='coordinators'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="users" class="h-4 w-4" /></span><span><strong>Coordinators</strong><small>Assignments</small></span>
                    </button>
                    @if($permissionAccess['manage_islands'] ?? false)<button type="button" @click="setTab('islands')" :aria-current="tab==='islands'?'page':null" class="operations-nav-item" :class="tab==='islands'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="building" class="h-4 w-4" /></span><span><strong>Islands</strong><small>Facilities</small></span>
                    </button>@endif
                    @if($permissionAccess['view_users'] ?? false)<button type="button" @click="setTab('users')" :aria-current="tab==='users'?'page':null" class="operations-nav-item" :class="tab==='users'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="users" class="h-4 w-4" /></span><span><strong>Users</strong><small>Accounts & roles</small></span>
                    </button>@endif
                    @if($permissionAccess['manage_departments'] ?? false)<button type="button" @click="setTab('user-departments')" :aria-current="tab==='user-departments'?'page':null" class="operations-nav-item" :class="tab==='user-departments'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="building-2" class="h-4 w-4" /></span><span><strong>Departments</strong><small>Staff groups</small></span>
                    </button>@endif
                    @if($permissionAccess['manage_departments'] ?? false)<button type="button" @click="setTab('departments')" :aria-current="tab==='departments'?'page':null" class="operations-nav-item" :class="tab==='departments'&&'is-active'">
                        <span class="operations-nav-icon"><x-icon name="tag" class="h-4 w-4" /></span><span><strong>Task Types</strong><small>Work categories</small></span>
                    </button>@endif
                  </div>
                  <div class="operations-nav-mobile-hint"><span>Swipe to explore</span><x-icon name="arrow-right" class="h-3.5 w-3.5" /></div>
                </nav>

                {{-- Overview --}}
                <div x-show="tab === 'overview'" x-cloak class="animate-fade-in text-sm text-muted-foreground">
                    Overview metrics are shown above.
                </div>

                {{-- Atolls (AtollManagement) --}}
                <div x-show="tab === 'atolls'" x-cloak x-data='atollManagement({ atolls: @json($atolls), coordinators: @json($coordinators) })' class="animate-fade-in">
                    <div class="card">
                        <div class="card-header flex flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="card-title flex items-center gap-2"><x-icon name="map-pin" class="h-5 w-5" /> Atolls</h3>
                                <p class="card-description">Manage atolls in the Maldives</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="openBulk()" class="btn btn-outline"><x-icon name="download" class="mr-2 h-4 w-4" /> Quick Import</button>
                                <button type="button" @click="openCreate()" class="btn"><x-icon name="plus" class="mr-2 h-4 w-4" /> Add Atoll</button>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="table-wrap overflow-x-auto rounded-lg border border-border">
                                <table class="table">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50">
                                            <th class="table-head">Name</th>
                                            <th class="table-head">Coordinator</th>
                                            <th class="table-head">Status</th>
                                            <th class="table-head">Created</th>
                                            <th class="table-head text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="a in atolls" :key="a.id">
                                            <tr class="table-row">
                                                <td class="table-cell font-medium" x-text="a.name"></td>
                                                <td class="table-cell">
                                                    <template x-if="a.coordinator_id">
                                                        <span class="badge border border-amber-200 bg-amber-50 text-amber-700" x-text="coordinatorName(a.coordinator_id)"></span>
                                                    </template>
                                                    <template x-if="!a.coordinator_id">
                                                        <span class="text-xs italic text-muted-foreground">Unassigned</span>
                                                    </template>
                                                </td>
                                                <td class="table-cell">
                                                    <span class="badge" :class="a.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'" x-text="a.status"></span>
                                                </td>
                                                <td class="table-cell" x-text="new Date(a.created_at).toLocaleDateString()"></td>
                                                <td class="table-cell text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        <button type="button" @click="openEdit(a)" class="btn btn-ghost btn-sm" aria-label="Edit atoll"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                        <button type="button" @click="remove(a.id)" class="btn btn-ghost btn-sm" aria-label="Delete atoll"><x-icon name="trash-2" class="h-4 w-4 text-destructive" /></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="atolls.length === 0">
                                            <tr><td colspan="5" class="table-cell py-8 text-center text-muted-foreground">No atolls found. Add your first atoll.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Atoll create/edit dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="dialogOpen" x-cloak @keydown.escape.window="dialogOpen = false" role="dialog" aria-modal="true" aria-label="Atoll details">
                        <div class="absolute inset-0 bg-black/60" @click="dialogOpen = false"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <h2 class="text-lg font-bold" x-text="editingAtoll ? 'Edit Atoll' : 'Create Atoll'"></h2>
                            <p class="text-sm text-muted-foreground" x-text="editingAtoll ? 'Update atoll information' : 'Add a new atoll information'"></p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="label" for="atollName">Atoll Name</label>
                                    <input id="atollName" type="text" x-model="formData.name" required placeholder="e.g., Kaafu" class="input">
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select x-model="formData.status" class="select-trigger">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Assigned Coordinator</label>
                                    <select x-model="formData.coordinator_id" class="select-trigger">
                                        <option value="">Unassigned</option>
                                        <template x-for="c in coordinators" :key="c.id">
                                            <option :value="c.id" x-text="c.first_name + ' ' + c.last_name"></option>
                                        </template>
                                    </select>
                                    <p class="text-[10px] text-muted-foreground">Coordinators manage all islands within this atoll.</p>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" @click="dialogOpen = false" class="btn btn-outline">Cancel</button>
                                <button type="button" @click="submit()" class="btn" x-text="editingAtoll ? 'Update' : 'Create'"></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coordinators (AtollCoordinators) --}}
                <div x-show="tab === 'coordinators'" x-cloak x-data='coordinatorManagement({ managers: @json($coordinators), allAtolls: @json($atolls), coordinatorsCount: @json(collect($coordinators)->where('role', 'coordinator')->count()), supervisorsCount: @json(collect($coordinators)->where('role', 'supervisor')->count()) })' class="animate-fade-in">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-black">Atoll Managers</h3>
                            <p class="text-sm text-muted-foreground">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span><span x-text="counts.coordinators + ' coordinator' + (counts.coordinators !== 1 ? 's' : '')"></span></span>
                                <span class="mx-2">·</span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-500"></span><span x-text="counts.supervisors + ' supervisor' + (counts.supervisors !== 1 ? 's' : '')"></span></span>
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="relative flex-1">
                                <x-icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input type="search" x-model="searchQuery" placeholder="Search by name, occupation, atoll, or role..." class="input h-10 rounded-xl pl-9">
                                <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2" aria-label="Clear search">
                                    <x-icon name="x" class="h-4 w-4 text-muted-foreground hover:text-foreground" />
                                </button>
                            </div>
                            <select x-model="atollFilter" class="select-trigger h-10 w-full rounded-xl sm:w-[200px]">
                                <option value="all">All Atolls</option>
                                <template x-for="a in allAtolls" :key="a.id">
                                    <option :value="a.id" x-text="a.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Desktop table --}}
                        <div class="hidden overflow-hidden rounded-xl border border-border md:block">
                            <table class="table">
                                <thead>
                                    <tr class="bg-muted/50">
                                        <th class="table-head text-xs font-bold uppercase tracking-wider">Name</th>
                                        <th class="table-head text-xs font-bold uppercase tracking-wider">Role</th>
                                        <th class="table-head text-xs font-bold uppercase tracking-wider">Occupation</th>
                                        <th class="table-head text-xs font-bold uppercase tracking-wider">Assigned Atoll(s)</th>
                                        <th class="table-head text-xs font-bold uppercase tracking-wider">Contact</th>
                                        <th class="table-head text-right text-xs font-bold uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="m in filteredManagers()" :key="m.role + '-' + m.id">
                                        <tr class="table-row transition-colors hover:bg-muted/30">
                                            <td class="table-cell"><span class="font-semibold" x-text="m.first_name + ' ' + m.last_name"></span></td>
                                            <td class="table-cell">
                                                <span class="badge border text-[9px] font-bold uppercase tracking-wider" :class="m.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'">
                                                    <x-icon name="user-cog" x-show="m.role === 'coordinator'" class="h-3 w-3" />
                                                    <x-icon name="shield" x-show="m.role === 'supervisor'" class="h-3 w-3" />
                                                    <span x-text="m.role === 'coordinator' ? 'Coordinator' : 'Supervisor'"></span>
                                                </span>
                                            </td>
                                            <td class="table-cell text-sm text-muted-foreground" x-text="m.designation || '—'"></td>
                                            <td class="table-cell">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="a in m.assigned_atolls" :key="a.id">
                                                        <span class="badge bg-secondary text-[10px] font-bold uppercase tracking-wider text-secondary-foreground" x-text="a.name"></span>
                                                    </template>
                                                    <template x-if="m.assigned_atolls.length === 0">
                                                        <span class="text-sm italic text-muted-foreground">Not assigned</span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="table-cell">
                                                <div class="flex flex-col gap-0.5">
                                                    <template x-if="m.contact_no">
                                                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                            <x-icon name="phone" class="h-3 w-3" /><span x-text="m.contact_no"></span>
                                                        </div>
                                                    </template>
                                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <x-icon name="mail" class="h-3 w-3" /><span x-text="m.email"></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-cell text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button type="button" @click="openView(m)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="View coordinator"><x-icon name="eye" class="h-4 w-4" /></button>
                                                    <button type="button" @click="openEdit(m)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="Edit coordinator"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                    <button type="button" @click="confirmDeactivate(m)" class="btn btn-ghost btn-icon h-8 w-8 text-destructive hover:text-destructive" aria-label="Deactivate coordinator"><x-icon name="trash-2" class="h-4 w-4" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredManagers().length === 0">
                                        <tr><td colspan="6" class="table-cell py-12 text-center text-muted-foreground">No managers match your filters.</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile cards --}}
                        <div class="space-y-3 md:hidden">
                            <template x-for="m in filteredManagers()" :key="m.role + '-' + m.id">
                                <div class="card overflow-hidden border-border/50 transition-colors hover:border-border">
                                    <div class="card-content p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="mb-1 flex items-center gap-2">
                                                    <h4 class="truncate font-bold" x-text="m.first_name + ' ' + m.last_name"></h4>
                                                    <template x-if="m.assigned_atolls.length === 0">
                                                        <span class="badge border border-amber-500/30 text-[9px] font-bold uppercase tracking-wider text-amber-500">Unassigned</span>
                                                    </template>
                                                </div>
                                                <span class="badge mb-2 border text-[9px] font-bold uppercase tracking-wider" :class="m.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'">
                                                    <x-icon name="user-cog" x-show="m.role === 'coordinator'" class="h-3 w-3" />
                                                    <x-icon name="shield" x-show="m.role === 'supervisor'" class="h-3 w-3" />
                                                    <span x-text="m.role === 'coordinator' ? 'Coordinator' : 'Supervisor'"></span>
                                                </span>
                                                <p class="mb-2 text-xs text-muted-foreground" x-text="m.designation || 'No designation'"></p>
                                                <div class="mb-2 flex flex-wrap gap-1">
                                                    <template x-for="a in m.assigned_atolls" :key="a.id">
                                                        <span class="badge bg-secondary text-[9px] font-bold uppercase tracking-wider" x-text="a.name"></span>
                                                    </template>
                                                </div>
                                                <div class="space-y-1">
                                                    <template x-if="m.contact_no">
                                                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                            <x-icon name="phone" class="h-3 w-3 shrink-0" /><span class="truncate" x-text="m.contact_no"></span>
                                                        </div>
                                                    </template>
                                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <x-icon name="mail" class="h-3 w-3 shrink-0" /><span class="truncate" x-text="m.email"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex shrink-0 flex-col gap-1">
                                                <button type="button" @click="openView(m)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="View coordinator"><x-icon name="eye" class="h-4 w-4" /></button>
                                                <button type="button" @click="openEdit(m)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="Edit coordinator"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                <button type="button" @click="confirmDeactivate(m)" class="btn btn-ghost btn-icon h-8 w-8 text-destructive hover:text-destructive" aria-label="Deactivate coordinator"><x-icon name="trash-2" class="h-4 w-4" /></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredManagers().length === 0">
                                <div class="py-12 text-center text-muted-foreground">No managers match your filters.</div>
                            </template>
                        </div>
                    </div>

                    {{-- Coordinator view dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="viewing" x-cloak @keydown.escape.window="viewing = null" role="dialog" aria-modal="true" aria-label="Coordinator details">
                        <div class="absolute inset-0 bg-black/60" @click="viewing = null"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <template x-if="viewing">
                                <div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-black text-primary" x-text="(viewing.first_name[0] || '') + (viewing.last_name ? viewing.last_name[0] : '')"></div>
                                        <div class="min-w-0">
                                            <h2 class="truncate text-lg font-bold" x-text="viewing.full_name"></h2>
                                            <span class="badge border text-[9px] font-bold uppercase tracking-wider" :class="viewing.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'" x-text="viewing.role === 'coordinator' ? 'Coordinator' : 'Supervisor'"></span>
                                        </div>
                                    </div>
                                    <div class="mt-5 space-y-3 text-sm">
                                        <template x-if="viewing.designation">
                                            <div class="flex items-center gap-2 text-muted-foreground"><x-icon name="briefcase" class="h-4 w-4 shrink-0" /><span x-text="viewing.designation"></span></div>
                                        </template>
                                        <div class="flex items-center gap-2 text-muted-foreground"><x-icon name="mail" class="h-4 w-4 shrink-0" /><span class="truncate" x-text="viewing.email"></span></div>
                                        <template x-if="viewing.contact_no">
                                            <div class="flex items-center gap-2 text-muted-foreground"><x-icon name="phone" class="h-4 w-4 shrink-0" /><span x-text="viewing.contact_no"></span></div>
                                        </template>
                                        <div class="flex items-center gap-2 text-muted-foreground"><x-icon name="map-pin" class="h-4 w-4 shrink-0" /><span x-text="viewing.assigned_atolls.length ? viewing.assigned_atolls.map(a => a.name).join(', ') : 'Not assigned'"></span></div>
                                        <div class="flex items-center gap-2 text-muted-foreground"><x-icon name="shield" class="h-4 w-4 shrink-0" /><span x-text="viewing.status"></span></div>
                                    </div>
                                    <div class="mt-6 flex justify-end gap-2">
                                        <button type="button" @click="viewing = null" class="btn btn-outline">Close</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Coordinator edit assignments dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="editing" x-cloak @keydown.escape.window="editing = null" role="dialog" aria-modal="true" aria-label="Edit coordinator">
                        <div class="absolute inset-0 bg-black/60" @click="editing = null"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <template x-if="editing">
                                <div>
                                    <h2 class="text-lg font-bold" x-text="'Assign Atolls — ' + editing.full_name"></h2>
                                    <p class="text-sm text-muted-foreground">Select the atolls this manager is responsible for.</p>
                                    <div class="mt-4 max-h-72 space-y-2 overflow-y-auto rounded-xl border border-border p-3">
                                        <template x-for="a in allAtolls" :key="a.id">
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50">
                                                <input type="checkbox" class="h-4 w-4 rounded border-border" :value="a.id" x-model="editForm.atoll_ids">
                                                <span class="text-sm font-medium" x-text="a.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <div class="mt-6 flex justify-end gap-2">
                                        <button type="button" @click="editing = null" class="btn btn-outline">Cancel</button>
                                        <button type="button" @click="saveEdit()" class="btn">Save Assignments</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Islands (IslandManagement) --}}
                <div x-show="tab === 'islands'" x-cloak x-data='islandManagement({ islands: @json($islands), atolls: @json($atolls), staff: @json($assignableStaff), staffDirectory: @json($profiles) })' class="animate-fade-in">
                    <div class="card">
                        <div class="card-header flex flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="card-title flex items-center gap-2"><x-icon name="palmtree" class="h-5 w-5" /> Islands</h3>
                                <p class="card-description">Manage islands within each atoll</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="openBulk()" class="btn btn-outline"><x-icon name="download" class="mr-2 h-4 w-4" /> Bulk Add</button>
                                <button type="button" @click="openCreate()" class="btn"><x-icon name="plus" class="mr-2 h-4 w-4" /> Add Island</button>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="mb-4">
                                <select x-model="filterAtoll" class="select-trigger w-full sm:w-64">
                                    <option value="all">All Atolls</option>
                                    <template x-for="a in atolls" :key="a.id">
                                        <option :value="a.id" x-text="a.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="table">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50">
                                            <th class="table-head">Atoll</th>
                                            <th class="table-head">Island Name</th>
                                            <th class="table-head">Assigned Staff</th>
                                            <th class="table-head">Status</th>
                                            <th class="table-head">Created</th>
                                            <th class="table-head text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="i in filteredIslands()" :key="i.id">
                                            <tr class="table-row">
                                                <td class="table-cell font-medium" x-text="atollName(i.atoll_id)"></td>
                                                <td class="table-cell" x-text="i.name"></td>
                                                <td class="table-cell">
                                                    <template x-if="staffName(i.assigned_staff_id)">
                                                        <span class="badge border border-teal-200 bg-teal-50 text-teal-700" x-text="staffName(i.assigned_staff_id)"></span>
                                                    </template>
                                                    <template x-if="!staffName(i.assigned_staff_id)">
                                                        <span class="text-xs italic text-muted-foreground">Unassigned</span>
                                                    </template>
                                                </td>
                                                <td class="table-cell">
                                                    <span class="badge" :class="i.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'" x-text="i.status"></span>
                                                </td>
                                                <td class="table-cell" x-text="new Date(i.created_at).toLocaleDateString()"></td>
                                                <td class="table-cell text-right">
                                                    <div class="inline-flex items-center gap-1">
                                                        <button type="button" @click="viewProfile(i)" class="btn btn-ghost btn-icon h-8 w-8" title="View Profile"><x-icon name="file-text" class="h-4 w-4 text-primary" /></button>
                                                        <button type="button" @click="openEdit(i)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="Edit island"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                        <button type="button" @click="remove(i.id)" class="btn btn-ghost btn-icon h-8 w-8" aria-label="Delete island"><x-icon name="trash-2" class="h-4 w-4 text-destructive" /></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="filteredIslands().length === 0">
                                            <tr><td colspan="6" class="table-cell py-8 text-center text-muted-foreground">No islands found. Add your first island.</td></tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Island create/edit dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="dialogOpen" x-cloak @keydown.escape.window="dialogOpen = false" role="dialog" aria-modal="true" aria-label="Island details">
                        <div class="absolute inset-0 bg-black/60" @click="dialogOpen = false"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <h2 class="text-lg font-bold" x-text="editingIsland ? 'Edit Island' : 'Create Island'"></h2>
                            <p class="text-sm text-muted-foreground" x-text="editingIsland ? 'Update island information' : 'Add a new island'"></p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="label" for="islandName">Island Name</label>
                                    <input id="islandName" type="text" x-model="formData.name" required placeholder="e.g., Malé" class="input">
                                </div>
                                <div>
                                    <label class="label">Atoll</label>
                                    <select x-model="formData.atoll_id" class="select-trigger">
                                        <option value="">Select atoll</option>
                                        <template x-for="a in atolls" :key="a.id">
                                            <option :value="a.id" x-text="a.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Assigned Staff</label>
                                    <select x-model="formData.assigned_staff_id" class="select-trigger">
                                        <option value="">Unassigned</option>
                                        <template x-for="s in staff" :key="s.id">
                                            <option :value="s.id" x-text="s.first_name + ' ' + (s.last_name || '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select x-model="formData.status" class="select-trigger">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" @click="dialogOpen = false" class="btn btn-outline">Cancel</button>
                                <button type="button" @click="submit()" class="btn" x-text="editingIsland ? 'Update' : 'Create'"></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Users (UserManagement) --}}
                <div x-show="tab === 'users'" x-cloak x-data='userManagement({ profiles: @json($profiles), userRoles: @json($userRoles), userDepartments: @json($userDepartments), currentUserId: @json(auth()->id()) })' class="animate-fade-in">
                    <div class="card">
                        <div class="card-header flex flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="card-title">Users</h3>
                                <p class="card-description">Manage employee accounts and access</p>
                            </div>
                            @if($permissionAccess['manage_users'] ?? false)<button type="button" @click="openCreate()" class="btn"><x-icon name="plus" class="mr-2 h-4 w-4" /> Add User</button>@endif
                        </div>
                        <div class="card-content">
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="table">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50">
                                            <th class="table-head">Name</th>
                                            <th class="table-head">Email</th>
                                            <th class="table-head">Department</th>
                                            <th class="table-head">Status</th>
                                            <th class="table-head">Role</th>
                                            <th class="table-head text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="p in profiles" :key="p.id">
                                            <tr class="table-row">
                                                <td class="table-cell font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <span x-text="p.first_name + ' ' + (p.last_name || '')"></span>
                                                        <x-icon name="shield" x-show="getRole(p.id) === 'admin'" class="h-4 w-4 text-primary" />
                                                        <x-icon name="eye" x-show="getRole(p.id) === 'supervisor'" class="h-4 w-4 text-blue-500" />
                                                        <x-icon name="eye" x-show="getRole(p.id) === 'staff'" class="h-4 w-4 text-green-500" />
                                                        <x-icon name="eye" x-show="getRole(p.id) === 'coordinator'" class="h-4 w-4 text-amber-500" />
                                                    </div>
                                                </td>
                                                <td class="table-cell">
                                                    <div class="flex items-center gap-2">
                                                        <x-icon name="mail" class="h-4 w-4 text-muted-foreground" />
                                                        <span x-text="p.email"></span>
                                                    </div>
                                                </td>
                                                <td class="table-cell" x-text="deptName(p.user_department_id)"></td>
                                                <td class="table-cell">
                                                    <span class="badge" :class="p.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'" x-text="p.status"></span>
                                                </td>
                                                <td class="table-cell">
                                                    <select class="select-trigger h-9 w-28" :value="getRole(p.id)" :disabled="{{ ($permissionAccess['manage_users'] ?? false) ? 'false' : 'true' }} || (p.id === currentUserId && getRole(p.id) === 'admin')" @change="updateRole(p, $event.target.value)">
                                                        <option value="admin">Admin</option>
                                                        <option value="supervisor">Supervisor</option>
                                                        <option value="coordinator">Coordinator</option>
                                                        <option value="staff">Staff</option>
                                                    </select>
                                                </td>
                                                <td class="table-cell text-right">
                                                    @if($permissionAccess['manage_users'] ?? false)<div class="inline-flex items-center gap-2">
                                                        <button type="button" @click="openEdit(p)" class="btn btn-ghost btn-sm" aria-label="Edit user"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                        <button type="button" @click="remove(p.id)" class="btn btn-ghost btn-sm" aria-label="Delete user"><x-icon name="trash-2" class="h-4 w-4 text-destructive" /></button>
                                                    </div>@endif
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- User create/edit dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="dialogOpen" x-cloak @keydown.escape.window="dialogOpen = false" role="dialog" aria-modal="true" aria-label="User details">
                        <div class="absolute inset-0 bg-black/60" @click="dialogOpen = false"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <h2 class="text-lg font-bold" x-text="editingProfile ? 'Edit User' : 'Create User'"></h2>
                            <p class="text-sm text-muted-foreground" x-text="editingProfile ? 'Update user information' : 'Create a new user account'"></p>
                            <div class="mt-4 space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="label" for="userFirstName">First Name</label>
                                        <input id="userFirstName" type="text" x-model="formData.first_name" required placeholder="e.g., Aishath" class="input">
                                    </div>
                                    <div>
                                        <label class="label" for="userLastName">Last Name</label>
                                        <input id="userLastName" type="text" x-model="formData.last_name" placeholder="e.g., Ali" class="input">
                                    </div>
                                </div>
                                <div>
                                    <label class="label" for="userEmail">Email</label>
                                    <input id="userEmail" type="email" x-model="formData.email" required placeholder="name@example.com" class="input">
                                </div>
                                <template x-if="!editingProfile">
                                    <div>
                                        <label class="label" for="userPassword">Password</label>
                                        <input id="userPassword" type="password" x-model="formData.password" required placeholder="Minimum 8 characters" class="input">
                                    </div>
                                </template>
                                <div>
                                    <label class="label" for="userContact">Contact Number</label>
                                    <input id="userContact" type="text" x-model="formData.contact_no" placeholder="e.g., 960 000-0000" class="input">
                                </div>
                                <div>
                                    <label class="label">Department</label>
                                    <select x-model="formData.user_department_id" class="select-trigger">
                                        <option value="">Unassigned</option>
                                        <template x-for="d in userDepartments" :key="d.id">
                                            <option :value="d.id" x-text="d.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select x-model="formData.status" class="select-trigger">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" @click="dialogOpen = false" class="btn btn-outline">Cancel</button>
                                <button type="button" @click="submit()" class="btn" x-text="editingProfile ? 'Update' : 'Create'"></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Departments (UserDepartmentManagement) --}}
                <div x-show="tab === 'user-departments'" x-cloak x-data='departmentManagement("/api/user-departments", { departments: @json($departments), userDepartments: @json($userDepartments) })' class="animate-fade-in">
                    <div class="card">
                        <div class="card-header flex flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="card-title flex items-center gap-2"><x-icon name="building" class="h-5 w-5" /> Departments</h3>
                                <p class="card-description">Organizational departments for assigning users</p>
                            </div>
                            <button type="button" @click="openCreate()" class="btn"><x-icon name="plus" class="mr-2 h-4 w-4" /> Add Department</button>
                        </div>
                        <div class="card-content">
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="table">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50">
                                            <th class="table-head">Color</th>
                                            <th class="table-head">Name</th>
                                            <th class="table-head">Description</th>
                                            <th class="table-head">Status</th>
                                            <th class="table-head text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="d in items" :key="d.id">
                                            <tr class="table-row">
                                                <td class="table-cell"><div class="h-6 w-6 rounded-full border border-border" :style="'background-color: ' + (d.color || '#3b82f6')"></div></td>
                                                <td class="table-cell font-medium" x-text="d.name"></td>
                                                <td class="table-cell text-sm text-muted-foreground" x-text="d.description"></td>
                                                <td class="table-cell">
                                                    <span class="badge" :class="d.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'" x-text="d.status"></span>
                                                </td>
                                                <td class="table-cell text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        <button type="button" @click="openEdit(d)" class="btn btn-ghost btn-sm" aria-label="Edit department"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                        <button type="button" @click="remove(d.id)" class="btn btn-ghost btn-sm" aria-label="Delete department"><x-icon name="trash-2" class="h-4 w-4 text-destructive" /></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Department create/edit dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="dialogOpen" x-cloak @keydown.escape.window="dialogOpen = false" role="dialog" aria-modal="true" aria-label="Department details">
                        <div class="absolute inset-0 bg-black/60" @click="dialogOpen = false"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <h2 class="text-lg font-bold" x-text="(editing ? 'Edit ' : 'Create ') + itemLabel"></h2>
                            <p class="text-sm text-muted-foreground" x-text="editing ? 'Update information' : 'Add a new ' + itemLabel.toLowerCase()"></p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="label" for="deptName">Name</label>
                                    <input id="deptName" type="text" x-model="form.name" required placeholder="e.g., Health Inspection" class="input">
                                </div>
                                <div>
                                    <label class="label" for="deptDesc">Description</label>
                                    <textarea id="deptDesc" x-model="form.description" rows="2" placeholder="Short description" class="textarea resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="label">Color</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="c in colors" :key="c">
                                            <button type="button" @click="form.color = c" class="h-8 w-8 rounded-full border-2 transition-transform hover:scale-110" :class="form.color === c ? 'border-foreground scale-110' : 'border-transparent'" :style="'background-color: ' + c"></button>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select x-model="form.status" class="select-trigger">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" @click="dialogOpen = false" class="btn btn-outline">Cancel</button>
                                <button type="button" @click="submit()" class="btn" x-text="editing ? 'Update' : 'Create'"></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Task Types (DepartmentManagement) --}}
                <div x-show="tab === 'departments'" x-cloak x-data='departmentManagement("/api/departments", { departments: @json($departments), userDepartments: @json($userDepartments) })' class="animate-fade-in">
                    <div class="card">
                        <div class="card-header flex flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="card-title flex items-center gap-2"><x-icon name="tag" class="h-5 w-5" /> Task Types</h3>
                                <p class="card-description">Manage task type categories</p>
                            </div>
                            <button type="button" @click="openCreate()" class="btn"><x-icon name="plus" class="mr-2 h-4 w-4" /> Add Task Type</button>
                        </div>
                        <div class="card-content">
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="table">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50">
                                            <th class="table-head">Color</th>
                                            <th class="table-head">Name</th>
                                            <th class="table-head">Status</th>
                                            <th class="table-head">Created</th>
                                            <th class="table-head text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="d in items" :key="d.id">
                                            <tr class="table-row">
                                                <td class="table-cell"><div class="h-6 w-6 rounded-full border border-border" :style="'background-color: ' + (d.color || '#3b82f6')"></div></td>
                                                <td class="table-cell font-medium" x-text="d.name"></td>
                                                <td class="table-cell">
                                                    <span class="badge" :class="d.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'" x-text="d.status"></span>
                                                </td>
                                                <td class="table-cell" x-text="new Date(d.created_at).toLocaleDateString()"></td>
                                                <td class="table-cell text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        <button type="button" @click="openEdit(d)" class="btn btn-ghost btn-sm" aria-label="Edit task type"><x-icon name="pencil" class="h-4 w-4" /></button>
                                                        <button type="button" @click="remove(d.id)" class="btn btn-ghost btn-sm" aria-label="Delete task type"><x-icon name="trash-2" class="h-4 w-4 text-destructive" /></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Task type create/edit dialog --}}
                    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="dialogOpen" x-cloak @keydown.escape.window="dialogOpen = false" role="dialog" aria-modal="true" aria-label="Task type details">
                        <div class="absolute inset-0 bg-black/60" @click="dialogOpen = false"></div>
                        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-xl border border-border bg-background p-6 shadow-2xl animate-zoom-in">
                            <h2 class="text-lg font-bold" x-text="(editing ? 'Edit ' : 'Create ') + itemLabel"></h2>
                            <p class="text-sm text-muted-foreground" x-text="editing ? 'Update information' : 'Add a new ' + itemLabel.toLowerCase()"></p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="label" for="taskTypeName">Name</label>
                                    <input id="taskTypeName" type="text" x-model="form.name" required placeholder="e.g., Inspection" class="input">
                                </div>
                                <div>
                                    <label class="label" for="taskTypeDesc">Description</label>
                                    <textarea id="taskTypeDesc" x-model="form.description" rows="2" placeholder="Short description" class="textarea resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="label">Color</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="c in colors" :key="c">
                                            <button type="button" @click="form.color = c" class="h-8 w-8 rounded-full border-2 transition-transform hover:scale-110" :class="form.color === c ? 'border-foreground scale-110' : 'border-transparent'" :style="'background-color: ' + c"></button>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select x-model="form.status" class="select-trigger">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <button type="button" @click="dialogOpen = false" class="btn btn-outline">Cancel</button>
                                <button type="button" @click="submit()" class="btn" x-text="editing ? 'Update' : 'Create'"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Non-admin: staff hospital profile --}}
        @if (!in_array($role, ['admin', 'supervisor'], true))
            <div class="space-y-6 sm:space-y-8">
                @if ($role === 'staff')
                    <div class="space-y-3 sm:space-y-4" x-data>
                        @if (!$hospitalProfile)
                            <div class="flex flex-col gap-3 rounded-2xl border border-primary/20 bg-primary/5 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                <div class="flex items-start gap-3">
                                    <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                    <div>
                                        <p class="text-sm font-semibold">Your hospital profile hasn't been set up yet</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">Update your facility's details so other users can view your hospital card on the Hospitals page.</p>
                                    </div>
                                </div>
                                @if ($assignedIsland)
                                    <button type="button" @click="$dispatch('hospital-edit')" class="btn btn-primary shrink-0">
                                        <x-icon name="pencil" class="mr-1.5 h-4 w-4" />
                                        Edit profile
                                    </button>
                                @endif
                            </div>
                        @endif
                        @include('partials.hospital-profile', [
                            'profile' => $hospitalProfile ?? null,
                            'islandName' => $assignedIsland->name ?? '',
                            'atollName' => $atollName ?? '',
                            'hospitalName' => $hospitalName ?? ($assignedIsland->name . ' Health Facility'),
                            'hospitalContactId' => $hospitalContactId ?? null,
                            'canEdit' => (bool) $assignedIsland,
                            'compact' => true,
                            'dashboardStyle' => true,
                        ])
                    </div>
                @endif
            </div>
        @endif
    </main>
@endsection
