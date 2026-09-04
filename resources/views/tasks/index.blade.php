@php
    $userRole = $role;
    $currentUserId = auth()->id();
@endphp

@extends('layouts.app')

@section('title', 'Task Manager')

@section('content')
<div x-data='taskManager({ tasks: @json($tasks), profiles: @json($profiles), assignableProfiles: @json($assignableProfiles), islands: @json($islands), atolls: @json($atolls), departments: @json($departments), archivedCounts: @json($archivedCounts), nextCursor: @json($nextCursor), userRole: @json($userRole), currentUserId: @json($currentUserId) })' x-init="init()" x-effect="syncUrl()" @keydown.escape.window="!createSaving && !uploading && (createDialogOpen=false); archiveDialogOpen=false" class="relative min-h-full bg-background">
    {{-- Ambient mesh gradient background --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 h-[420px] w-[720px] -translate-x-1/2 rounded-full bg-primary/10 blur-[120px]"></div>
        <div class="absolute -right-24 top-1/3 h-[300px] w-[300px] rounded-full bg-accent/10 blur-[100px]"></div>
        <div class="absolute -left-24 bottom-0 h-[300px] w-[300px] rounded-full bg-primary/5 blur-[100px]"></div>
    </div>

    <div class="relative">
        <x-dashboard-header
            title="Task"
            titleAccent="Manager"
            subtitle="Official Task Management"
            onRefresh="window.location.reload()"
            :exportUrl="in_array($userRole, ['admin', 'supervisor'], true) ? url('/api/reports/export/tasks') : null"
            exportLabel="Export"
        />

        <div class="px-4 pb-24 lg:px-8 lg:pb-8">
            {{-- Task statistics --}}
            <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <template x-for="stat in statCards" :key="stat.key">
                    <button
                        type="button"
                        @click="handleStatClick(stat.key)"
                        class="group relative overflow-hidden rounded-xl p-4 text-left shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                        :class="stat.gradient + (filters.status === stat.key ? ' ring-2 ring-offset-2 ring-offset-background ring-foreground/30' : '')"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20 text-white">
                                <x-icon name="activity" class="h-4 w-4" x-show="stat.key === 'total'" />
                                <x-icon name="hourglass" class="h-4 w-4" x-show="stat.key === 'pending'" />
                                <x-icon name="zap" class="h-4 w-4" x-show="stat.key === 'in_progress'" />
                                <x-icon name="badge-check" class="h-4 w-4" x-show="stat.key === 'completed'" />
                                <x-icon name="flame" class="h-4 w-4" x-show="stat.key === 'overdue'" />
                                <x-icon name="target" class="h-4 w-4" x-show="stat.key === 'efficiency'" />
                            </div>
                            <span x-show="filters.status === stat.key" class="absolute right-3 top-3 h-2 w-2 rounded-full bg-white/70"></span>
                        </div>
                        <p class="mt-3 text-2xl font-black leading-none text-white" x-text="stat.value"></p>
                        <p class="mt-1.5 text-[9px] font-black uppercase tracking-[0.2em] text-white/80" x-text="stat.label"></p>
                        <div
                            class="absolute bottom-0 left-0 h-0.5 w-full scale-x-0 bg-white/50 transition-transform duration-300 group-hover:scale-x-100"
                            :class="filters.status === stat.key ? 'scale-x-100' : ''"
                        ></div>
                    </button>
                </template>
            </div>

            {{-- Task menu bar --}}
            <div class="mb-6 rounded-xl border border-border bg-card shadow-sm">
                <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between lg:gap-4">
                    <div class="relative flex-1 lg:max-w-xs">
                        <x-icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="search"
                            x-model="filters.search"
                            placeholder="Search tasks..."
                            class="input h-11 w-full rounded-lg border-border bg-slate-50 pl-10 font-medium dark:border-slate-700 dark:bg-slate-800"
                        />
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <select x-model="filters.atoll" @change="filters.island = ''" class="select-trigger h-11 w-full rounded-lg border-border bg-slate-50 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800 sm:w-44">
                            <option value="">All Atolls</option>
                            <template x-for="atoll in atolls" :key="atoll.id">
                                <option :value="atoll.id" x-text="atoll.name"></option>
                            </template>
                        </select>
                        <select x-model="filters.island" class="select-trigger h-11 w-full rounded-lg border-border bg-slate-50 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800 sm:w-44">
                            <option value="">All Islands</option>
                            <template x-for="island in formIslands(filters.atoll)" :key="island.id">
                                <option :value="island.id" x-text="island.name"></option>
                            </template>
                        </select>

                        {{-- Advanced popover --}}
                        <div class="relative" x-data="{ advancedOpen: false }" @click.outside="advancedOpen = false">
                            <button
                                type="button"
                                @click="advancedOpen = !advancedOpen"
                                class="flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-border bg-slate-50 px-4 text-xs font-black uppercase tracking-widest transition-colors hover:bg-muted dark:border-slate-700 dark:bg-slate-800 sm:w-auto"
                                :class="filters.status || filters.user ? 'text-primary' : 'text-slate-500'"
                            >
                                <x-icon name="sliders-horizontal" class="h-4 w-4" />
                                Advanced
                                <span x-show="filters.status || filters.user" x-cloak class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                            </button>
                            <div x-show="advancedOpen" x-cloak class="absolute right-0 z-40 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-border bg-card p-4 shadow-xl animate-fade-in">
                                <p class="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Advanced Filters</p>
                                <div class="space-y-3">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Status</label>
                                        <select x-model="filters.status" class="select-trigger h-10 w-full rounded-md border-border bg-slate-50 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800">
                                            <option value="">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Assignee</label>
                                        <select x-model="filters.user" class="select-trigger h-10 w-full rounded-md border-border bg-slate-50 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800">
                                            <option value="">All Assignees</option>
                                            <template x-for="p in profiles" :key="p.id">
                                                <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="filters.status = ''; filters.user = ''"
                                    class="mt-4 h-9 w-full rounded-md border border-border text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-muted"
                                >
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex rounded-lg border border-border bg-slate-50 p-0.5 dark:border-slate-700 dark:bg-slate-800">
                            <button
                                type="button"
                                @click="setViewMode('list')"
                                class="flex h-9 w-9 items-center justify-center rounded-md transition-colors"
                                :class="viewMode === 'list' ? 'bg-primary text-white shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                title="List view"
                            >
                                <x-icon name="list" class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                @click="setViewMode('grouped')"
                                class="flex h-9 w-9 items-center justify-center rounded-md transition-colors"
                                :class="viewMode === 'grouped' ? 'bg-primary text-white shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                title="Grouped view"
                            >
                                <x-icon name="layout-grid" class="h-4 w-4" />
                            </button>
                        </div>

                        @if(\App\Models\RolePermission::allows('create_tasks'))<button type="button" @click="openCreate()" class="btn h-11 items-center gap-2 rounded-lg px-4 text-xs font-black uppercase tracking-widest">
                            <x-icon name="plus" class="h-4 w-4" />
                            Create Task
                        </button>@endif
                    </div>
                </div>

                {{-- Active filters --}}
                <template x-if="hasActiveFilters">
                    <div class="flex flex-wrap items-center gap-2 border-t border-border px-4 py-3">
                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Active Filters</span>
                        <template x-if="filters.search">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                Search: <span class="max-w-[160px] truncate" x-text="filters.search"></span>
                                <button type="button" @click="filters.search = ''" class="touch-target -m-3 inline-flex items-center justify-center hover:text-primary/70" aria-label="Clear search filter"><x-icon name="x" class="h-3 w-3" /></button>
                            </span>
                        </template>
                        <template x-if="filters.atoll">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                <span x-text="atollName(filters.atoll)"></span>
                                <button type="button" @click="filters.atoll = ''; filters.island = ''" class="touch-target -m-3 inline-flex items-center justify-center hover:text-primary/70" aria-label="Clear atoll filter"><x-icon name="x" class="h-3 w-3" /></button>
                            </span>
                        </template>
                        <template x-if="filters.island">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                <span x-text="islandName(filters.island)"></span>
                                <button type="button" @click="filters.island = ''" class="touch-target -m-3 inline-flex items-center justify-center hover:text-primary/70" aria-label="Clear island filter"><x-icon name="x" class="h-3 w-3" /></button>
                            </span>
                        </template>
                        <template x-if="filters.status">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                <span x-text="statusLabel(filters.status)"></span>
                                <button type="button" @click="filters.status = ''" class="touch-target -m-3 inline-flex items-center justify-center hover:text-primary/70" aria-label="Clear status filter"><x-icon name="x" class="h-3 w-3" /></button>
                            </span>
                        </template>
                        <template x-if="filters.user">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                <span x-text="profileName(filters.user)"></span>
                                <button type="button" @click="filters.user = ''" class="touch-target -m-3 inline-flex items-center justify-center hover:text-primary/70" aria-label="Clear user filter"><x-icon name="x" class="h-3 w-3" /></button>
                            </span>
                        </template>
                        <button type="button" @click="resetFilters()" class="ml-auto text-[10px] font-black uppercase tracking-widest text-muted-foreground underline-offset-2 hover:text-foreground hover:underline">
                            Clear All
                        </button>
                    </div>
                </template>
            </div>
{{-- List view --}}
            <template x-if="viewMode === 'list'">
                <div class="space-y-4">
                    <template x-for="task in filteredTasks()" :key="task.id">
                        @include('partials.task-card')
                    </template>

                    <template x-if="nextCursor && filteredTasks().length > 0">
                        <button
                            type="button"
                            @click="loadMore()"
                            :disabled="loadingMore"
                            class="mx-auto flex w-full items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-[10px] font-black uppercase tracking-widest text-muted-foreground transition-colors hover:bg-muted/60"
                        >
                            <x-icon name="plus" class="h-4 w-4" x-show="!loadingMore" />
                            <span x-text="loadingMore ? 'Loading more tasks…' : 'Load More Tasks'"></span>
                        </button>
                    </template>

                    <template x-if="filteredTasks().length === 0">
                        <div class="rounded-xl border border-dashed border-border bg-card p-16 text-center animate-fade-in">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                <x-icon name="list-todo" class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <p class="text-lg font-black uppercase tracking-tight">No Tasks Found</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm font-medium text-muted-foreground">
                                No official tasks match your current filters. Adjust the search criteria or initialize a new operation.
                            </p>
                            @if(\App\Models\RolePermission::allows('create_tasks'))<button type="button" @click="openCreate()" class="btn mt-6 items-center gap-2 rounded-lg px-4 text-xs font-black uppercase tracking-widest">
                                <x-icon name="plus" class="h-4 w-4" />
                                Initialize New Task
                            </button>@endif
                        </div>
                    </template>

                    <template x-if="nextCursor && filteredTasks().length > 0">
                        <button
                            type="button"
                            @click="loadMore()"
                            :disabled="loadingMore"
                            class="mx-auto flex w-full items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-[10px] font-black uppercase tracking-widest text-muted-foreground transition-colors hover:bg-muted/60"
                        >
                            <x-icon name="plus" class="h-4 w-4" x-show="!loadingMore" />
                            <span x-text="loadingMore ? 'Loading more tasks…' : 'Load More Tasks'"></span>
                        </button>
                    </template>
                </div>
            </template>

            {{-- Grouped view --}}
            <template x-if="viewMode === 'grouped'">
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground" x-text="filteredTasks().length + ' Official Tasks'"></p>
                        <div class="flex gap-2">
                            <button type="button" @click="expandAll()" class="rounded-md border border-border px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-muted">
                                Expand All
                            </button>
                            <button type="button" @click="collapseAll()" class="rounded-md border border-border px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-muted">
                                Collapse All
                            </button>
                        </div>
                    </div>

                    <template x-for="group in groupedTasks()" :key="group.atoll ? group.atoll.id : 'unassigned'">
                        <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                            <button
                                type="button"
                                @click="toggleAtoll(group)"
                                class="flex w-full items-center justify-between bg-slate-50 px-5 py-4 text-left transition-colors hover:bg-muted/60 dark:bg-slate-800/50"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg" :class="group.atoll ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                                        <x-icon name="palmtree" x-show="group.atoll" class="h-4 w-4" />
                                        <x-icon name="globe" x-show="!group.atoll" class="h-4 w-4" />
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black uppercase tracking-tight" x-text="group.atoll ? group.atoll.name : 'Unassigned'"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground" x-text="group.taskCount + ' Task' + (group.taskCount === 1 ? '' : 's')"></span>
                                    </div>
                                </div>
                                <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform duration-200" x-bind:class="isAtollExpanded(group) ? '' : '-rotate-90'" />
                            </button>

                            <template x-if="isAtollExpanded(group)">
                                <div class="divide-y divide-border">
                                    <template x-for="sub in group.islands" :key="sub.island ? sub.island.id : 'unassigned'">
                                        <div>
                                            <button
                                                type="button"
                                                @click="toggleIsland(sub)"
                                                class="flex w-full items-center justify-between px-5 py-3 text-left transition-colors hover:bg-muted/40"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                                        <x-icon name="map-pin" class="h-3.5 w-3.5" />
                                                    </span>
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-black uppercase tracking-tight" x-text="sub.island ? sub.island.name : 'Global Portal'"></span>
                                                        <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground" x-text="sub.taskCount + ' Task' + (sub.taskCount === 1 ? '' : 's')"></span>
                                                    </div>
                                                </div>
                                                <x-icon name="chevron-down" class="h-3.5 w-3.5 text-muted-foreground transition-transform duration-200" x-bind:class="isIslandExpanded(sub) ? '' : '-rotate-90'" />
                                            </button>

                                            <template x-if="isIslandExpanded(sub)">
                                                <div class="space-y-4 bg-background/50 p-4">
                                                    <template x-for="task in sub.tasks" :key="task.id">
                                                        @include('partials.task-card')
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="filteredTasks().length === 0">
                        <div class="rounded-xl border border-dashed border-border bg-card p-16 text-center animate-fade-in">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                <x-icon name="layout-grid" class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <p class="text-lg font-black uppercase tracking-tight">No Tasks Found</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm font-medium text-muted-foreground">
                                No official tasks match your current filters. Adjust the search criteria or initialize a new operation.
                            </p>
                            @if(\App\Models\RolePermission::allows('create_tasks'))<button type="button" @click="openCreate()" class="btn mt-6 items-center gap-2 rounded-lg px-4 text-xs font-black uppercase tracking-widest">
                                <x-icon name="plus" class="h-4 w-4" />
                                Initialize New Task
                            </button>@endif
                        </div>
                    </template>
                </div>
            </template>

            {{-- System archive banner --}}
            <template x-if="userRole !== 'staff' && (archivedCounts.completed > 0 || archivedCounts.cancelled > 0)">
                <button
                    type="button"
                    @click="archiveDialogOpen = true"
                    class="mt-8 flex w-full items-center justify-between rounded-xl border border-dashed border-border bg-card px-5 py-4 text-left transition-all hover:border-primary/40 hover:bg-muted/40"
                >
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                            <x-icon name="archive" class="h-5 w-5" />
                        </span>
                        <div class="flex flex-col">
                            <span class="text-xs font-black uppercase tracking-tight">System Archive</span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">
                                <span x-text="archivedCounts.completed + archivedCounts.cancelled"></span> archived official record(s) awaiting review
                            </span>
                        </div>
                    </div>
                    <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-primary">
                        Review
                        <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                    </span>
                </button>
            </template>
        </div>
    </div>
{{-- Create task dialog --}}
    <template x-teleport="body">
    <div class="mobile-dialog fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="createDialogOpen" x-cloak @keydown.escape.window="!createSaving && !uploading && (createDialogOpen = false)" role="dialog" aria-modal="true" aria-label="Create task">
        <button type="button" class="absolute inset-0 bg-black/60" @click="!createSaving && !uploading && (createDialogOpen = false)" aria-label="Close create task dialog"></button>
        <div class="mobile-dialog-panel relative z-10 flex max-h-[calc(100dvh-0.5rem)] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl border border-border bg-card shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-3rem)] sm:rounded-xl">
            <div class="flex items-center justify-between px-6 pt-6">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Initialize New Operation</h3>
                    <p class="text-sm font-semibold text-slate-500">Formally register a new official task.</p>
                </div>
                <button type="button" @click="createDialogOpen = false" :disabled="createSaving || uploading" class="touch-target inline-flex items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted" aria-label="Close create task dialog">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submitCreate()" class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-5 pb-6 pt-5 [-webkit-overflow-scrolling:touch] sm:px-6 sm:pt-6">
                <div x-show="createError" x-cloak role="alert" class="rounded-xl border border-destructive/25 bg-destructive/10 px-4 py-3 text-sm font-semibold text-destructive" x-text="createError"></div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Formal Title</label>
                    <input type="text" x-data="thaanaInput()" @input="autoTag" x-model="createForm.title" placeholder="e.g. Emergency Medical Supply Delivery" class="input h-11 w-full rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Detailed Description</label>
                    <textarea x-data="thaanaInput()" @input="autoTag" x-model="createForm.creator_description" rows="3" placeholder="Describe the scope and objectives of this operation..." class="textarea w-full rounded-md border-slate-200 bg-slate-50 font-medium dark:border-slate-700 dark:bg-slate-800"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div x-show="userRole === 'staff' && islands.length === 1" class="space-y-2 sm:col-span-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Assigned hospital</label>
                        <div class="flex min-h-11 items-center gap-3 rounded-md border border-primary/20 bg-primary/5 px-3.5 text-sm font-bold text-foreground">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-icon name="building-2" class="h-4 w-4" /></span>
                            <span x-text="assignedFacilityLabel()"></span>
                            <span class="ml-auto rounded-full bg-primary/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-primary">Assigned</span>
                        </div>
                        <p class="text-xs font-medium text-muted-foreground">Staff tasks are restricted to this assigned hospital.</p>
                    </div>
                    <div x-show="userRole !== 'staff' || islands.length !== 1" class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Atoll</label>
                        <select x-model="createForm.atoll_id" @change="createForm.island_id = ''" class="select-trigger h-11 w-full rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select Atoll...</option>
                            <template x-for="atoll in atolls" :key="atoll.id">
                                <option :value="atoll.id" x-text="atoll.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="userRole !== 'staff' || islands.length !== 1" class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Island</label>
                        <select x-model="createForm.island_id" class="select-trigger h-11 w-full rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800" :disabled="!createForm.atoll_id">
                            <option value="">Select Island...</option>
                            <template x-for="island in formIslands(createForm.atoll_id)" :key="island.id">
                                <option :value="island.id" x-text="island.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Assign To</label>
                        <select x-model="createForm.assigned_to" class="select-trigger h-11 w-full rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Unassigned</option>
                            <template x-for="p in assignableProfiles" :key="p.id">
                                <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Priority Level</label>
                        <div class="relative" @click.outside="priorityOpen = false" @keydown.escape.window="priorityOpen = false">
                            <button type="button" @click="priorityOpen = !priorityOpen" class="premium-select-trigger" :aria-expanded="priorityOpen" aria-haspopup="listbox">
                                <span class="flex items-center gap-2.5"><span class="h-2.5 w-2.5 rounded-full" :class="priorityOption(createForm.priority).dot"></span><span x-text="priorityOption(createForm.priority).label"></span></span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform duration-200" x-bind:class="priorityOpen ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="priorityOpen" x-cloak x-transition.origin.top class="premium-select-menu absolute inset-x-0 top-full z-40 mt-2 p-1.5" role="listbox" aria-label="Priority level">
                                <template x-for="option in priorityOptions" :key="option.value">
                                    <button type="button" role="option" @click="createForm.priority = option.value; priorityOpen = false" class="premium-select-option" :class="createForm.priority === option.value ? 'is-selected' : ''" :aria-selected="createForm.priority === option.value">
                                        <span class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full" :class="option.dot"></span><span><span class="block text-sm font-bold" x-text="option.label"></span><span class="block text-[11px] text-muted-foreground" x-text="option.help"></span></span></span>
                                        <x-icon name="check" x-show="createForm.priority === option.value" class="h-4 w-4 text-primary" />
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Deadline</label>
                    <div class="relative" @click.outside="calendarOpen = false" @keydown.escape.window="calendarOpen = false">
                        <button type="button" @click="openCalendar()" class="premium-date-trigger" :aria-expanded="calendarOpen" aria-haspopup="dialog">
                            <span class="flex items-center gap-3"><span class="premium-date-icon"><x-icon name="calendar" class="h-4 w-4" /></span><span class="text-left"><span class="block text-[11px] font-semibold text-muted-foreground">Select deadline</span><span class="block text-sm font-bold" :class="createForm.due_date ? 'text-foreground' : 'text-muted-foreground'" x-text="calendarDateLabel()"></span></span></span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform duration-200" x-bind:class="calendarOpen ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="calendarOpen" x-cloak x-transition.origin.top class="premium-calendar mt-2 w-full p-4 sm:absolute sm:bottom-full sm:left-0 sm:z-40 sm:mb-2 sm:mt-0 sm:w-[340px]" role="dialog" aria-label="Choose task deadline">
                            <div class="flex items-center justify-between">
                                <button type="button" @click="changeCalendarMonth(-1)" class="premium-calendar-nav" aria-label="Previous month"><x-icon name="chevron-left" class="h-4 w-4" /></button>
                                <div class="text-center"><p class="text-sm font-black" x-text="calendarMonthLabel()"></p><button type="button" @click="goCalendarToday()" class="mt-0.5 text-[11px] font-semibold text-primary hover:underline">Today</button></div>
                                <button type="button" @click="changeCalendarMonth(1)" class="premium-calendar-nav" aria-label="Next month"><x-icon name="chevron-right" class="h-4 w-4" /></button>
                            </div>
                            <div class="mt-4 grid grid-cols-7 text-center"><template x-for="dayName in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="dayName"><span class="py-1 text-[10px] font-black uppercase tracking-wider text-muted-foreground" x-text="dayName"></span></template></div>
                            <div class="mt-1 grid grid-cols-7 gap-1">
                                <template x-for="day in calendarDays()" :key="day.value"><button type="button" @click="selectCalendarDate(day.value)" class="premium-calendar-day" :class="{ 'is-outside': !day.currentMonth, 'is-today': day.today, 'is-selected': day.selected }" :aria-label="day.label" :aria-pressed="day.selected" x-text="day.day"></button></template>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-border/60 pt-3"><button type="button" @click="createForm.due_date = ''; calendarOpen = false" class="text-xs font-semibold text-muted-foreground hover:text-foreground">Clear date</button><button type="button" @click="calendarOpen = false" class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-primary-foreground">Done</button></div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Task Classification</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="t in departments" :key="t.id">
                            <button
                                type="button"
                                @click="toggleTaskType(t.name)"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                                :style="createForm.task_types.includes(t.name) ? 'background-color: ' + (t.color || '#3b82f6') + '20; color: ' + (t.color || '#3b82f6') + '; border-color: ' + (t.color || '#3b82f6') : ''"
                                :class="!createForm.task_types.includes(t.name) ? 'border-border bg-background text-muted-foreground hover:bg-muted' : ''"
                            >
                                <x-icon name="check" x-show="createForm.task_types.includes(t.name)" class="h-3 w-3" />
                                <span x-text="t.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Attachment</label>
                    <div class="flex items-center gap-2">
                        <template x-if="createForm.attachment_url">
                            <a :href="createForm.attachment_url" target="_blank" class="flex flex-1 items-center gap-2 rounded-md border border-border bg-slate-50 px-3 py-2 text-xs font-semibold text-primary hover:bg-muted">
                                <x-icon name="paperclip" class="h-4 w-4" />
                                <span class="truncate" x-text="fileName(createForm.attachment_url)"></span>
                            </a>
                        </template>
                        <label class="flex h-11 cursor-pointer items-center gap-2 rounded-md border border-border px-3 text-xs font-bold text-slate-500 transition-colors hover:bg-muted" :class="createForm.attachment_url ? '' : 'flex-1 justify-center'">
                            <x-icon name="upload" class="h-4 w-4" />
                            <span x-text="createForm.attachment_url ? 'Replace' : 'Attach File'"></span>
                            <input type="file" class="hidden" @change="onFileChange($event)">
                        </label>
                        <button type="button" x-show="createForm.attachment_url" @click="createForm.attachment_url = ''" class="flex h-11 w-11 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-muted" aria-label="Remove attachment">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="safe-bottom grid shrink-0 grid-cols-2 gap-2 border-t border-border/60 px-4 py-3 sm:flex sm:justify-end sm:px-6 sm:py-4">
                <button type="button" @click="createDialogOpen = false" :disabled="createSaving || uploading" class="h-11 rounded-md px-3 text-xs font-bold uppercase tracking-widest text-slate-600 transition-colors hover:bg-muted dark:text-slate-300 sm:px-4">
                    Discard Entry
                </button>
                <button type="submit" :disabled="uploading || createSaving" class="inline-flex h-11 min-w-[8.5rem] items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 sm:px-5">
                    <x-icon name="loader-2" x-show="uploading || createSaving" x-cloak class="h-4 w-4 animate-spin" />
                    <span>Add task</span>
                </button>
            </div>
            </form>
        </div>
    </div>
    </template>

    {{-- System archive dialog --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="archiveDialogOpen" x-cloak role="dialog" aria-modal="true" aria-label="Task archive">
        <button type="button" class="absolute inset-0 bg-black/60" @click="archiveDialogOpen = false" aria-label="Close task archive"></button>
        <div class="relative z-10 max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-hidden rounded-xl border border-border bg-card shadow-xl animate-zoom-in">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">System Archive</h3>
                    <p class="text-sm font-semibold text-slate-500">Completed tasks auto-archive after 3 days and are purged after 30 days.</p>
                </div>
                <button type="button" @click="archiveDialogOpen = false" class="touch-target inline-flex items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted" aria-label="Close task archive">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3 border-b border-border p-6">
                <div class="rounded-xl bg-emerald-50 p-4 text-center dark:bg-emerald-900/20">
                    <p class="text-3xl font-black text-emerald-600" x-text="archivedCounts.completed"></p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-emerald-600/80">Completed</p>
                </div>
                <div class="rounded-xl bg-rose-50 p-4 text-center dark:bg-rose-900/20">
                    <p class="text-3xl font-black text-rose-600" x-text="archivedCounts.cancelled"></p>
                    <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-rose-600/80">Cancelled</p>
                </div>
            </div>
            <div class="max-h-72 overflow-y-auto p-3">
                <template x-for="task in archivedTasks" :key="task.id">
                    <div class="flex items-center justify-between rounded-lg px-3 py-2.5 transition-colors hover:bg-muted/50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-200" x-text="task.title"></p>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground" x-text="formatDate(task.due_date)"></p>
                        </div>
                        <span class="ml-3 shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase tracking-widest" :class="task.status === 'completed' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-rose-500/20 bg-rose-500/10 text-rose-600'" x-text="task.status"></span>
                    </div>
                </template>
                <template x-if="archivedTasks.length === 0">
                    <p class="py-8 text-center text-sm font-medium text-muted-foreground">No archived records found.</p>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
