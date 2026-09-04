@extends('layouts.app')

@section('title', 'Hospitals')

@section('content')
<div x-data='hospitalsPage({ contacts: @json($contacts), atolls: @json($atolls), islands: @json($islands), role: @json($role), editableIslandIds: @json($editableIslandIds->values()), coverage: @json($coverage ?? ['updated' => 0, 'total' => 0, 'missing' => []]), canManageHospitals: @json(\App\Models\RolePermission::allows('manage_hospitals')), canEditProfiles: @json(\App\Models\RolePermission::allows('edit_hospital_profiles')) })' class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Hospital Directory</h1>
            <p class="text-sm text-muted-foreground" x-text="contacts.length + ' facilities'"></p>
        </div>
        <div class="page-header-actions flex w-full flex-wrap gap-2 sm:w-auto">
            <button type="button" @click="exportCsv()" class="gov-btn gov-btn-outline text-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span class="ml-1.5">Export</span>
            </button>
            @if (\App\Models\RolePermission::allows('manage_hospitals'))
                <button type="button" @click="importOpen = true" class="gov-btn gov-btn-outline text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="ml-1.5">Import</span>
                </button>
                <button type="button" @click="openCreate()" class="gov-btn gov-btn-primary text-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span class="ml-1.5">Add Contact</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="gov-card mb-6 p-3">
        <div class="grid grid-cols-1 items-center gap-2 md:grid-cols-[minmax(0,1fr)_11rem_11rem]">
            <div class="relative min-w-0">
                <svg class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-model="filters.search" placeholder="Search hospitals or personnel..." class="h-9 w-full rounded-md border border-border bg-background pl-9 text-sm">
            </div>
            <select x-model="filters.atoll" @change="filters.island = 'all'" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs">
                <option value="all">All Atolls</option>
                <template x-for="a in atolls" :key="a.id">
                    <option :value="a.id" x-text="a.name"></option>
                </template>
            </select>
            <select x-model="filters.island" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs">
                <option value="all">All Islands</option>
                <template x-for="i in islandsForFilter()" :key="i.id">
                    <option :value="i.id" x-text="i.name"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- Bulk actions --}}
    <div x-show="selected.size > 0" x-cloak class="mb-4 flex items-center justify-between rounded-lg border border-border bg-muted/40 px-3 py-2">
        <span class="text-xs text-muted-foreground" x-text="selected.size + ' selected'"></span>
        @if ($role === 'admin')
            <button type="button" @click="deactivateSelected()" class="text-xs font-medium text-destructive hover:underline">Delete selected</button>
        @endif
    </div>

    {{-- Coverage --}}
    @if (in_array($role, ['admin', 'supervisor'], true))
        <div class="gov-card mb-6 p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-3 sm:gap-6">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold">Hospital profile coverage</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground" x-text="coverage.updated + ' of ' + coverage.total + ' islands updated within the last 30 days'"></p>
                </div>
                <div class="h-2 w-full max-w-xs flex-1 overflow-hidden rounded-full bg-muted sm:min-w-[160px]">
                    <div class="h-full rounded-full bg-primary transition-all" :style="'width: ' + (coverage.total ? Math.round(coverage.updated / coverage.total * 100) : 0) + '%'"></div>
                </div>
                <template x-if="coverage.missing.length">
                    <p class="w-full text-xs text-muted-foreground sm:w-auto sm:flex-1">
                        <span class="font-semibold text-amber-600 dark:text-amber-400">Needs update:</span>
                        <span x-text="coverage.missing.join(', ')"></span>
                    </p>
                </template>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="gov-card hidden overflow-hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[820px]">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        @if ($role === 'admin')
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" class="h-4 w-4 rounded border-border" :checked="allSelected()" @change="toggleSelectAll($event.target.checked)">
                            </th>
                        @endif
                        <th class="px-4 py-3 font-semibold">Facility</th>
                        <th class="px-4 py-3 font-semibold">Location</th>
                        <th class="px-4 py-3 font-semibold">Manager</th>
                        <th class="px-4 py-3 font-semibold">Contact</th>
                        <th class="px-4 py-3 font-semibold">Profile</th>
                        @if ($role === 'admin')
                            <th class="px-4 py-3 text-right font-semibold"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in filteredContacts()" :key="c.id">
                        <tr class="group border-t border-border">
                            @if (\App\Models\RolePermission::allows('manage_hospitals'))
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="h-4 w-4 rounded border-border" :checked="selected.has(c.id)" :disabled="c.island_facility" @change="toggleSelect(c.id, $event.target.checked, c.island_facility)">
                                </td>
                            @endif
                            <td class="max-w-[240px] px-4 py-3 font-medium">
                                <button type="button" @click="openProfile(c)" class="inline-flex max-w-full items-center gap-2 rounded-md text-left text-foreground transition-colors hover:text-primary focus-visible:text-primary" :aria-label="`Open hospital profile for ${c.hospital_name}`">
                                    <x-icon name="file-text" class="h-4 w-4 shrink-0 text-primary" />
                                    <span class="truncate underline-offset-4 hover:underline" x-text="c.hospital_name"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="c.island?.name ? (c.island.name + (c.island?.atoll?.name ? ' (' + c.island.atoll.name + ')' : '')) : 'Not set'"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p x-text="c.manager_name"></p>
                                <template x-if="c.contact_designation">
                                    <p class="text-xs text-muted-foreground" x-text="c.contact_designation"></p>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <a :href="'tel:' + c.contact_number" class="inline-flex items-center gap-1.5 font-medium text-primary hover:underline">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span x-text="c.contact_number"></span>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="c.profile_preview">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-medium tabular-nums" x-text="c.profile_preview.beds ? c.profile_preview.beds + ' beds' : 'No bed data'"></span>
                                        <span class="text-xs text-muted-foreground" x-text="[c.profile_preview.grade ? 'Grade ' + c.profile_preview.grade : '', c.profile_preview.population ? Number(c.profile_preview.population).toLocaleString() + ' pop' : ''].filter(Boolean).join(' · ')"></span>
                                    </div>
                                </template>
                                <template x-if="!c.profile_preview">
                                    <span class="text-xs font-medium italic text-muted-foreground">Not updated yet</span>
                                </template>
                            </td>
                            @if ($role === 'admin')
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openProfile(c)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-muted-foreground hover:border-primary/30 hover:bg-primary/5 hover:text-primary" :aria-label="`View hospital profile for ${c.hospital_name}`" title="View hospital profile">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </button>
                                        <button x-show="!c.island_facility" type="button" @click="openEdit(c)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-muted-foreground hover:border-primary/30 hover:bg-primary/5 hover:text-primary" :aria-label="`Edit ${c.hospital_name}`" title="Edit contact">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button x-show="!c.island_facility" type="button" @click="deactivate(c)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-muted-foreground hover:border-destructive/30 hover:bg-destructive/5 hover:text-destructive" :aria-label="`Deactivate ${c.hospital_name}`" title="Deactivate">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    </template>
                    <template x-if="filteredContacts().length === 0">
                        <tr>
                            <td :colspan="'{{ $role === 'admin' ? 6 : 5 }}'" class="h-32 text-center text-sm text-muted-foreground">
                                <span x-text="hasActiveFilters() ? 'No facilities match the current filters' : 'No facilities registered yet'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile facility cards --}}
    <div class="space-y-3 md:hidden">
        <template x-for="c in filteredContacts()" :key="c.id + '-mobile'">
            <article class="gov-card overflow-hidden p-4">
                <div class="flex items-start gap-3">
                    @if (\App\Models\RolePermission::allows('manage_hospitals'))
                        <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-border" :checked="selected.has(c.id)" :disabled="c.island_facility" @change="toggleSelect(c.id, $event.target.checked, c.island_facility)" :aria-label="`Select ${c.hospital_name}`">
                    @endif
                    <button type="button" @click="openProfile(c)" class="min-w-0 flex-1 text-left" :aria-label="`Open hospital profile for ${c.hospital_name}`">
                        <span class="block truncate text-base font-bold text-foreground" x-text="c.hospital_name"></span>
                        <span class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground"><x-icon name="map-pin" class="h-4 w-4 shrink-0" /><span class="truncate" x-text="c.island?.name ? (c.island.name + (c.island?.atoll?.name ? ' · ' + c.island.atoll.name : '')) : 'Location not set'"></span></span>
                    </button>
                    <button type="button" @click="openProfile(c)" class="touch-target inline-flex shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary" :aria-label="`View ${c.hospital_name}`"><x-icon name="chevron-right" class="h-5 w-5" /></button>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-xl bg-muted/45 p-3"><span class="block text-xs text-muted-foreground">Manager</span><span class="mt-0.5 block truncate font-semibold" x-text="c.manager_name || 'Not assigned'"></span></div>
                    <div class="rounded-xl bg-muted/45 p-3"><span class="block text-xs text-muted-foreground">Profile</span><span class="mt-0.5 block truncate font-semibold" x-text="c.profile_preview?.beds ? c.profile_preview.beds + ' beds' : 'Not updated'"></span></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a :href="'tel:' + c.contact_number" class="gov-btn gov-btn-outline flex-1"><x-icon name="phone" class="h-4 w-4" /><span x-text="c.contact_number || 'No number'"></span></a>
                    @if ($role === 'admin')
                        <button x-show="!c.island_facility" type="button" @click="openEdit(c)" class="gov-btn gov-btn-outline btn-icon" :aria-label="`Edit ${c.hospital_name}`"><x-icon name="pencil" class="h-4 w-4" /></button>
                        <button x-show="!c.island_facility" type="button" @click="deactivate(c)" class="gov-btn gov-btn-outline btn-icon text-destructive" :aria-label="`Deactivate ${c.hospital_name}`"><x-icon name="trash-2" class="h-4 w-4" /></button>
                    @endif
                </div>
            </article>
        </template>
        <div x-show="filteredContacts().length === 0" class="gov-card px-5 py-12 text-center text-sm text-muted-foreground" x-text="hasActiveFilters() ? 'No facilities match the current filters' : 'No facilities registered yet'"></div>
    </div>

{{-- Add / Edit dialog --}}
<template x-teleport="body">
<div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showForm = false" @keydown.escape.window="showForm = false" role="dialog" aria-modal="true" aria-label="Hospital contact details">
    <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-lg bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
        <h2 class="text-lg font-bold mb-1" x-text="editing ? 'Edit Contact' : 'Add Contact'"></h2>
        <p class="text-xs text-muted-foreground mb-4">Facility contact details</p>
        <form @submit.prevent="save()" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-muted-foreground mb-1">Facility name *</label>
                <input type="text" x-model="form.hospital_name" required class="gov-input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-muted-foreground mb-1">Atoll</label>
                    <select x-model="form.atoll_id" @change="form.island_id = ''" class="gov-input">
                        <option value="">Select atoll</option>
                        <template x-for="a in atolls" :key="a.id">
                            <option :value="a.id" x-text="a.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted-foreground mb-1">Island</label>
                    <select x-model="form.island_id" class="gov-input" :disabled="!form.atoll_id">
                        <option value="" x-text="form.atoll_id ? 'Select island' : 'Select atoll first'"></option>
                        <template x-for="i in islandsByAtoll(form.atoll_id)" :key="i.id">
                            <option :value="i.id" x-text="i.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-muted-foreground mb-1">Manager name *</label>
                <input type="text" x-model="form.manager_name" required class="gov-input">
            </div>
            <div>
                <label class="block text-xs font-medium text-muted-foreground mb-1">Contact number *</label>
                <input type="text" x-model="form.contact_number" required class="gov-input">
            </div>
            <div>
                <label class="block text-xs font-medium text-muted-foreground mb-1">Designation</label>
                <input type="text" x-model="form.contact_designation" class="gov-input">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showForm = false" class="gov-btn gov-btn-outline">Cancel</button>
                <button type="submit" class="gov-btn gov-btn-primary" x-text="editing ? 'Save changes' : 'Add contact'"></button>
            </div>
        </form>
    </div>
</div>
</template>

{{-- Import dialog --}}
<template x-teleport="body">
<div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="importOpen = false" @keydown.escape.window="importOpen = false" role="dialog" aria-modal="true" aria-label="Import hospital contacts">
    <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-lg bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
        <h2 class="text-lg font-bold mb-1">Import Contacts</h2>
        <p class="text-sm text-muted-foreground mb-4">Upload a CSV file with hospital contact data.</p>
        <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-10 bg-muted/20">
            <svg class="h-8 w-8 text-muted-foreground mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-sm font-medium mb-1">Select CSV file</p>
            <p class="text-xs text-muted-foreground mb-4">Max 5MB</p>
            <button type="button" @click="$refs.csvInput.click()" class="gov-btn gov-btn-outline text-xs">Browse</button>
            <input type="file" x-ref="csvInput" class="hidden" accept=".csv" @change="handleFileUpload($event)">
        </div>
        <div class="mt-4 flex items-center justify-between rounded-md bg-muted/30 px-4 py-3">
            <p class="text-xs text-muted-foreground">Download template</p>
            <button type="button" @click="downloadTemplate()" class="gov-btn gov-btn-outline text-xs">Template</button>
        </div>
    </div>
</div>
</template>

{{-- Import preview dialog --}}
<template x-teleport="body">
<div x-show="showPreview && parsedData.length > 0" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="closePreview()" @keydown.escape.window="closePreview()" role="dialog" aria-modal="true" aria-label="Import preview">
    <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-4xl bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap gap-2">
                <span class="badge bg-muted text-muted-foreground" x-text="parsedData.length + ' rows found'"></span>
                <span class="badge bg-emerald-600 text-white" x-text="matchedCount() + ' matched'"></span>
                <template x-if="unmatchedCount() > 0">
                    <span class="badge bg-muted text-muted-foreground" x-text="unmatchedCount() + ' unmatched (will import without island)'"></span>
                </template>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="selectAllRows()" class="gov-btn gov-btn-outline text-xs">Select All</button>
                <button type="button" @click="deselectAllRows()" class="gov-btn gov-btn-outline text-xs">Deselect All</button>
            </div>
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Exact match
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Partial match
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                No match
            </span>
        </div>

        <div class="max-h-[350px] overflow-y-auto rounded-md border border-border">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-muted/50 text-left sticky top-0">
                    <tr>
                        <th class="w-12 px-3 py-2"></th>
                        <th class="px-3 py-2 font-semibold">Hospital</th>
                        <th class="px-3 py-2 font-semibold">Island (CSV)</th>
                        <th class="px-3 py-2 font-semibold">Matched Location</th>
                        <th class="px-3 py-2 font-semibold">Manager</th>
                        <th class="px-3 py-2 font-semibold">Contact</th>
                        <th class="px-3 py-2 font-semibold">Designation</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in parsedData" :key="index">
                        <tr class="border-t border-border cursor-pointer" :class="!selectedRows.has(index) ? 'opacity-50' : ''" @click="toggleRow(index)">
                            <td class="px-3 py-2" @click.stop>
                                <input type="checkbox" class="h-4 w-4 rounded border-border" :checked="selectedRows.has(index)" @change="toggleRow(index)">
                            </td>
                            <td class="px-3 py-2 font-medium" x-text="row.hospital_name || '-'"></td>
                            <td class="px-3 py-2 text-muted-foreground" x-text="row.island_name || row.atoll_name ? (row.island_name + (row.atoll_name ? ' (' + row.atoll_name + ')' : '')) : '-'"></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center gap-2">
                                    <svg x-show="row.matchType === 'exact'" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <svg x-show="row.matchType === 'partial'" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <svg x-show="row.matchType === 'none'" class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <template x-if="row.matchedIsland">
                                        <span x-text="row.matchedIsland.name + (row.matchedAtoll ? ' (' + row.matchedAtoll.name + ')' : '')"></span>
                                    </template>
                                    <template x-if="!row.matchedIsland">
                                        <span class="text-muted-foreground">No match</span>
                                    </template>
                                </span>
                            </td>
                            <td class="px-3 py-2" x-text="row.manager_name || '-'"></td>
                            <td class="px-3 py-2" x-text="row.contact_number || '-'"></td>
                            <td class="px-3 py-2" x-text="row.contact_designation || '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" @click="closePreview()" class="gov-btn gov-btn-outline" :disabled="importing">Back</button>
            <button type="button" @click="confirmImport()" class="gov-btn gov-btn-primary" :disabled="importing || validCount() === 0">
                <span x-text="importing ? 'Importing...' : 'Import ' + validCount() + ' Contact' + (validCount() !== 1 ? 's' : '')"></span>
            </button>
        </div>
    </div>
</div>
</template>

{{-- Hospital profile dialog --}}
<template x-teleport="body">
<div x-show="profileOpen" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/60 p-0 backdrop-blur-[2px] sm:items-center sm:p-5" @click.self="closeProfile()" @keydown.escape.window="profileOpen && closeProfile()" role="dialog" aria-modal="true" aria-labelledby="hospital-profile-title">
    <article x-show="profileOpen" x-transition:enter="transition duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-[.97]" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100" class="hospital-profile-shell flex max-h-[96dvh] w-full flex-col overflow-hidden rounded-t-[2rem] bg-background sm:max-h-[92dvh] sm:max-w-5xl sm:rounded-[2rem]">
        <header class="hospital-profile-hero relative shrink-0 overflow-hidden px-5 py-5 sm:px-8 sm:py-7">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-primary to-emerald-400"></div>
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-primary/10 blur-3xl"></div>
            <div class="flex items-start gap-4">
                <div class="hospital-profile-emblem hidden h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-primary sm:flex">
                    <x-icon name="building-2" class="h-6 w-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="mb-1.5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active facility</span>
                        <span x-show="profile.grade" class="rounded-full border border-border bg-muted/60 px-2.5 py-1 text-xs font-semibold text-muted-foreground">Grade <span x-text="profile.grade"></span></span>
                    </div>
                    <h2 id="hospital-profile-title" class="text-xl font-bold leading-tight tracking-tight sm:text-2xl" x-text="profileHospitalName"></h2>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="h-4 w-4" /><span x-text="profileLocation()"></span></span>
                        <span x-show="profileContact?.manager_name" class="inline-flex items-center gap-1.5"><x-icon name="user" class="h-4 w-4" /><span x-text="profileContact?.manager_name"></span></span>
                    </p>
                </div>
                <button x-show="profileCanEdit && !profileEditing && !profileLoading" type="button" @click="beginProfileEdit()" class="gov-btn gov-btn-outline hidden sm:inline-flex"><x-icon name="pencil" class="h-4 w-4" />Edit profile</button>
                <button type="button" @click="closeProfile()" class="touch-target -mr-2 -mt-2 inline-flex shrink-0 items-center justify-center rounded-xl text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Close hospital profile">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
        </header>

        <div class="hospital-profile-canvas min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <div x-show="profileLoading" class="space-y-5 p-5 sm:p-7" aria-live="polite" aria-label="Loading hospital profile">
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4"><template x-for="i in 4" :key="i"><div class="h-28 animate-pulse rounded-2xl bg-muted"></div></template></div>
                <div class="grid gap-5 lg:grid-cols-3"><div class="h-72 animate-pulse rounded-2xl bg-muted lg:col-span-2"></div><div class="h-72 animate-pulse rounded-2xl bg-muted"></div></div>
            </div>

            <div x-show="!profileLoading" class="p-5 sm:p-7">
                <section aria-labelledby="capacity-heading">
                    <div class="mb-3 flex items-end justify-between"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">At a glance</p><h3 id="capacity-heading" class="mt-1 text-lg font-bold">Capacity & activity</h3></div><p class="hidden text-xs text-muted-foreground sm:block">Current facility profile</p></div>
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="hospital-profile-metric group"><span class="hospital-profile-icon bg-blue-500/10 text-blue-600 dark:text-blue-300"><x-icon name="bed" class="h-4.5 w-4.5" /></span><p class="mt-5 text-3xl font-bold tracking-tight tabular-nums" x-text="formatMetric(profile.no_of_beds)"></p><p class="mt-1 text-sm font-medium text-muted-foreground">Licensed beds</p></div>
                        <div class="hospital-profile-metric group"><span class="hospital-profile-icon bg-cyan-500/10 text-cyan-700 dark:text-cyan-300"><x-icon name="users" class="h-4.5 w-4.5" /></span><p class="mt-5 text-3xl font-bold tracking-tight tabular-nums" x-text="formatMetric(totalStaff())"></p><p class="mt-1 text-sm font-medium text-muted-foreground">Total workforce</p></div>
                        <div class="hospital-profile-metric group"><span class="hospital-profile-icon bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"><x-icon name="activity" class="h-4.5 w-4.5" /></span><p class="mt-5 text-3xl font-bold tracking-tight tabular-nums"><span x-text="activeServices()"></span><span class="text-base font-semibold text-muted-foreground"> / 8</span></p><p class="mt-1 text-sm font-medium text-muted-foreground">Active services</p></div>
                        <div class="hospital-profile-metric group"><span class="hospital-profile-icon bg-violet-500/10 text-violet-700 dark:text-violet-300"><x-icon name="map-pin" class="h-4.5 w-4.5" /></span><p class="mt-5 text-3xl font-bold tracking-tight tabular-nums" x-text="formatMetric(profile.population)"></p><p class="mt-1 text-sm font-medium text-muted-foreground">Population served</p></div>
                    </div>
                    <div x-show="profileEditing" class="mt-3 rounded-2xl border border-primary/20 bg-primary/[.04] p-4">
                        <p class="mb-3 text-sm font-bold">Facility metrics</p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <template x-for="field in profileMetricFields" :key="field.field"><label class="block"><span class="mb-1.5 block text-xs font-semibold text-muted-foreground" x-text="field.label"></span><input :type="field.type || 'number'" min="0" x-model="profile[field.field]" class="gov-input" :aria-label="field.label"></label></template>
                        </div>
                    </div>
                </section>

                <div class="mt-6 grid gap-5 lg:grid-cols-3">
                    <div class="space-y-5 lg:col-span-2">
                        <section class="hospital-profile-panel" aria-labelledby="workforce-heading">
                            <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">People</p><h3 id="workforce-heading" class="mt-1 text-lg font-bold">Workforce composition</h3></div><span class="rounded-lg bg-muted px-3 py-1.5 text-sm font-bold tabular-nums" x-text="totalStaff() + ' staff'"></span></div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-border/70 bg-muted/20 p-4"><div class="flex items-center justify-between"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-icon name="stethoscope" class="h-4.5 w-4.5" /></span><strong class="text-2xl tabular-nums" x-text="totalMedicalStaff()"></strong></div><p class="mt-3 text-sm font-semibold">Medical</p><p class="mt-0.5 text-xs text-muted-foreground">Doctors and specialists</p></div>
                                <div class="rounded-xl border border-border/70 bg-muted/20 p-4"><div class="flex items-center justify-between"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-700 dark:text-cyan-300"><x-icon name="heart" class="h-4.5 w-4.5" /></span><strong class="text-2xl tabular-nums" x-text="totalNursingStaff()"></strong></div><p class="mt-3 text-sm font-semibold">Nursing</p><p class="mt-0.5 text-xs text-muted-foreground">Clinical nursing team</p></div>
                                <div class="rounded-xl border border-border/70 bg-muted/20 p-4"><div class="flex items-center justify-between"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300"><x-icon name="briefcase" class="h-4.5 w-4.5" /></span><strong class="text-2xl tabular-nums" x-text="totalAdminStaff()"></strong></div><p class="mt-3 text-sm font-semibold">Support</p><p class="mt-0.5 text-xs text-muted-foreground">Admin and operations</p></div>
                            </div>
                        </section>

                        <section class="hospital-profile-panel" aria-labelledby="staff-detail-heading">
                            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">Complete register</p><h3 id="staff-detail-heading" class="mt-1 text-lg font-bold">Detailed workforce</h3></div><p class="text-xs text-muted-foreground">All staffing categories</p></div>
                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <template x-for="group in staffGroups" :key="group.id">
                                    <div class="overflow-hidden rounded-xl border border-border/70" :class="group.id === 'medical' ? 'md:row-span-2' : ''">
                                        <div class="flex items-center justify-between border-b border-border/70 bg-muted/30 px-4 py-3"><div><p class="text-sm font-bold" x-text="group.label"></p><p class="text-xs text-muted-foreground" x-text="group.description"></p></div><span class="rounded-md bg-background px-2 py-1 text-xs font-bold tabular-nums" x-text="staffGroupTotal(group)"></span></div>
                                        <dl class="divide-y divide-border/50">
                                            <template x-for="row in group.fields" :key="row.field">
                                                <div class="flex min-h-11 items-center justify-between gap-3 px-4 py-2"><dt class="text-sm text-muted-foreground" x-text="row.label"></dt><dd><input x-show="profileEditing" type="number" min="0" inputmode="numeric" x-model.number="profile[row.field]" class="gov-input h-9 min-h-9 w-20 px-2 text-center text-sm font-bold tabular-nums" :aria-label="row.label"><span x-show="!profileEditing" class="inline-flex min-w-10 justify-center rounded-md bg-muted px-2 py-1 text-sm font-bold tabular-nums" x-text="formatMetric(profile[row.field] || 0)"></span></dd></div>
                                            </template>
                                        </dl>
                                    </div>
                                </template>
                            </div>
                        </section>

                        <section class="hospital-profile-panel" aria-labelledby="services-heading">
                            <div><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">Clinical coverage</p><h3 id="services-heading" class="mt-1 text-lg font-bold">Available services</h3></div>
                            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                                <template x-for="service in serviceFields" :key="service.field">
                                    <button type="button" @click="profileEditing && (profile[service.field] = !profile[service.field])" :disabled="!profileEditing" class="flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition" :class="(profile[service.field] ? 'border-emerald-500/25 bg-emerald-500/[.07]' : 'border-border bg-muted/20') + (profileEditing ? ' cursor-pointer hover:border-primary/40' : ' cursor-default')">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="profile[service.field] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'"><x-icon name="check" x-show="profile[service.field]" class="h-4 w-4" /><x-icon name="x" x-show="!profile[service.field]" class="h-4 w-4" /></span>
                                        <div class="min-w-0 flex-1"><p class="text-sm font-semibold" x-text="service.label"></p><p class="text-xs text-muted-foreground" x-text="profile[service.field] ? 'Available' : 'Not available'"></p></div>
                                    </button>
                                </template>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-5">
                        <section class="hospital-profile-panel !overflow-hidden !p-0" aria-labelledby="activity-heading">
                            <div class="border-b border-border px-5 py-4"><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">Patient activity</p><h3 id="activity-heading" class="mt-1 text-lg font-bold">Care volume</h3></div>
                            <div class="divide-y divide-border">
                                <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="text-sm font-semibold">Outpatients</p><p class="text-xs text-muted-foreground">Average per day</p></div><p class="text-2xl font-bold tabular-nums" x-text="formatMetric(profile.avg_outpatient_per_day)"></p></div>
                                <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="text-sm font-semibold">Inpatients</p><p class="text-xs text-muted-foreground">Average per month</p></div><p class="text-2xl font-bold tabular-nums" x-text="formatMetric(profile.avg_inpatient_per_month)"></p></div>
                                <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="text-sm font-semibold">Ambulances</p><p class="text-xs text-muted-foreground"><span x-text="formatMetric(profile.ambulance_running_condition)"></span> operational</p></div><p class="text-2xl font-bold tabular-nums" x-text="formatMetric(profile.ambulance_total)"></p></div>
                            </div>
                        </section>

                        <section class="hospital-profile-panel" aria-labelledby="readiness-heading">
                            <div class="flex items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-primary">Readiness</p><h3 id="readiness-heading" class="mt-1 text-lg font-bold">Status overview</h3></div><span class="rounded-full bg-primary/[.07] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-primary">6 checks</span></div>
                            <div class="mt-4 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-1">
                                <template x-for="status in statusFields" :key="status.field">
                                    <div class="rounded-2xl bg-muted/30 p-1 ring-1 ring-border/60">
                                        <div class="rounded-[calc(1rem-0.25rem)] bg-card px-3.5 py-3.5 shadow-[inset_0_1px_0_hsl(var(--background))]">
                                            <div class="flex min-w-0 items-start justify-between gap-3">
                                                <div class="flex min-w-0 items-start gap-2.5">
                                                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full ring-4" :class="statusTone(profile[status.field]).dot"></span>
                                                    <p class="text-sm font-semibold leading-5 text-foreground" x-text="status.label"></p>
                                                </div>
                                                <span x-show="!profileEditing" class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold leading-4" :class="statusTone(profile[status.field]).badge" x-text="profile[status.field] || 'Not reported'"></span>
                                            </div>
                                            <div x-show="profileEditing" class="mt-3 border-t border-border/50 pt-3">
                                                <label class="block">
                                                    <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-[.12em] text-muted-foreground">Current condition</span>
                                                    <select x-model="profile[status.field]" class="gov-input min-h-11 w-full px-3 text-sm font-semibold" :aria-label="status.label">
                                                        <option value="">Not reported</option>
                                                        <template x-for="option in profileStatusOptions" :key="option"><option :value="option" x-text="option"></option></template>
                                                    </select>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </aside>
                </div>

                <section x-show="profileEditing || profile.project_information || profile.other_information" class="hospital-profile-panel mt-5" aria-labelledby="notes-heading">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-primary">Context</p><h3 id="notes-heading" class="mt-1 text-lg font-bold">Operational notes</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-border/70 bg-muted/20 p-4"><p class="text-sm font-semibold">Projects & improvements</p><textarea x-show="profileEditing" x-model="profile.project_information" rows="4" class="gov-input mt-3 min-h-28 resize-y" placeholder="Enter project details"></textarea><p x-show="!profileEditing" class="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground" x-text="profile.project_information || 'No project information recorded.'"></p></div>
                        <div class="rounded-xl border border-border/70 bg-muted/20 p-4"><p class="text-sm font-semibold">Additional information</p><textarea x-show="profileEditing" x-model="profile.other_information" rows="4" class="gov-input mt-3 min-h-28 resize-y" placeholder="Enter additional information"></textarea><p x-show="!profileEditing" class="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground" x-text="profile.other_information || 'No additional information recorded.'"></p></div>
                    </div>
                </section>
            </div>
        </div>

        <footer class="flex shrink-0 items-center justify-between gap-3 border-t border-border bg-card px-5 py-3.5 sm:px-7">
            <p class="hidden text-xs text-muted-foreground sm:block" x-text="profileEditing ? 'Editing the complete hospital profile' : 'Hospital operational profile'"></p>
            <button x-show="profileCanEdit && !profileEditing" type="button" @click="beginProfileEdit()" class="gov-btn gov-btn-outline sm:hidden"><x-icon name="pencil" class="h-4 w-4" />Edit</button>
            <div x-show="profileEditing" class="ml-auto flex gap-2"><button type="button" @click="cancelProfileEdit()" class="gov-btn gov-btn-outline" :disabled="profileSaving">Cancel</button><button type="button" @click="saveProfile()" class="gov-btn gov-btn-primary min-w-32" :disabled="profileSaving"><x-icon name="loader-2" x-show="profileSaving" class="h-4 w-4 animate-spin" /><x-icon name="save" x-show="!profileSaving" class="h-4 w-4" /><span x-text="profileSaving ? 'Saving…' : 'Save profile'"></span></button></div>
            <button x-show="!profileEditing" type="button" @click="closeProfile()" class="gov-btn gov-btn-primary ml-auto min-w-28">Done</button>
        </footer>
    </article>
</div>
</template>
</div>
@endsection
